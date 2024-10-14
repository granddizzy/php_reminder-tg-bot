<?php

namespace tests;

use App\Application;
use App\Commands\HandleEventsDaemonCommand;
use PHPUnit\Framework\TestCase;

class HandleEventsDaemonCommandTest extends TestCase {
  public function testGetCurrentTime() {
    $handleEventsDaemonCommand = new HandleEventsDaemonCommand(new Application(dirname(__DIR__)));
    $result = $handleEventsDaemonCommand->getCurrentTime();

    $this->assertIsArray($result);
    $this->assertCount(5, $result);

    $this->assertMatchesRegularExpression('/^\d{2}$/', $result[0]); // минуты
    $this->assertMatchesRegularExpression('/^(0[0-9]|1[0-9]|2[0-3])$/', $result[1]); // часы
    $this->assertMatchesRegularExpression('/^(0[1-9]|[12][0-9]|3[01])$/', $result[2]); // день месяца
    $this->assertMatchesRegularExpression('/^(0[1-9]|1[0-2])$/', $result[3]); // месяц
    $this->assertMatchesRegularExpression('/^[0-6]$/', $result[4]); // день недели
  }

  public function testGetLastDataWhenCacheExists() {
    // Записываем данные в кэш
    file_put_contents(HandleEventsDaemonCommand::CACHE_PATH, json_encode([0, 0, 0, 0, 0]));

    $handleEventsDaemonCommand = new HandleEventsDaemonCommand(new Application(dirname(__DIR__)));
    $result = $handleEventsDaemonCommand->getLastData();

    // Проверяем, что данные из кэша корректно возвращаются
    $this->assertIsArray($result);
    $this->assertCount(5, $result);
    $this->assertEquals([0, 0, 0, 0, 0], $result);
  }
}