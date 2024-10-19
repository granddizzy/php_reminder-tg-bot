<?php

namespace App\Cache;

use Predis\Client as RedisClient;
use Psr\SimpleCache\CacheInterface;

class Redis implements CacheInterface {
  private RedisClient $client;

  public function __construct(RedisClient $client) {
    $this->client = $client;
  }

  /**
   * @inheritDoc
   */
  public function get(string $key, mixed $default = null): mixed {
    return $this->client->get($key) ?? $default;
  }

  /**
   * @inheritDoc
   */
  public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool {
    if ($ttl) {
      $this->client->set($key, $value, 'EX', $this->convertTtlToSeconds($ttl));
    } else {
      $this->client->set($key, $value);
    }

    return true;
  }

  /**
   * @inheritDoc
   */
  public function delete(string $key): bool {
    $this->client->del($key);

    return true;
  }

  /**
   * @inheritDoc
   */
  public function clear(): bool {
    $this->client->flushAll();

    return true;
  }

  /**
   * @inheritDoc
   */
  public function getMultiple(iterable $keys, mixed $default = null): iterable {
    $values = $this->client->mget($keys);

    return array_map(function ($value) use ($default) {
      return $value !== null ? $value : $default;
    }, $values);
  }

  /**
   * @inheritDoc
   */
  public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool {
    $this->client->multi();

    foreach ($values as $key => $value) {
      $this->client->set($key, $value);

      if ($ttl !== null) {
        $ttlSeconds = $this->convertTtlToSeconds($ttl);
        $this->client->expire($key, $ttlSeconds);
      }
    }

    $results = $this->client->exec();

    foreach ($results as $result) {
      if ($result === false) {
        return false;
      }
    }

    return true;
  }

  function convertTtlToSeconds(int|null $ttl): int {
    if ($ttl instanceof \DateInterval) {
      return (new \DateTime)->setTimestamp(0)->add($ttl)->getTimestamp();
    }

    return $ttl;
  }

  /**
   * @inheritDoc
   */
  public function deleteMultiple(iterable $keys): bool {
    $this->client->del($keys);

    return true;
  }

  /**
   * @inheritDoc
   */
  public function has(string $key): bool {
    return $this->client->exists($key) == 1;
  }
}