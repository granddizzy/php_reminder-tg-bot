<?php

declare(ticks=1);

namespace App\Commands;

use App\Application;

class QueueManagerDaemonCommand extends Command {
  protected Application $app;

//  const CACHE_PATH = __DIR__ . '/../../cache2.txt';

  public function __construct(Application $app) {
    $this->app = $app;
  }

  public function run(array $options = []): void {
    $this->initPcntl();
    $this->daemonRun($options);
  }

  private function initPcntl(): void {
    $callback = function ($signal) {
      switch ($signal) {
        case SIGTERM:
        case SIGINT:
        case SIGHUP:
//          $lastData = $this->getCurrentTime();
//          $lastData[0] = $lastData[0] - 1;
//
//          file_put_contents(self::CACHE_PATH, json_encode($lastData));
          exit;
      }
    };

    pcntl_signal(SIGTERM, $callback);
    pcntl_signal(SIGHUP, $callback);
    pcntl_signal(SIGINT, $callback);
  }

  private function daemonRun(array $options) {
    $queueManagerCommand = new QueueManagerCommand($this->app);
    $queueManagerCommand->run($options);
  }

//  private function getCurrentTime(): array {
//    return [
//      date("i"),
//      date("H"),
//      date("d"),
//      date("m"),
//      date("w")
//    ];
//  }

//  private function getLastData(): array {
//    if (!file_exists(self::CACHE_PATH)) {
//      file_put_contents(self::CACHE_PATH, "");
//    }
//    $lastData = file_get_contents(self::CACHE_PATH);
//
//    if ($lastData) {
//      return json_decode($lastData);
//    }
//
//    return [];
//  }
}