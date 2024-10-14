<?php

namespace tests;

use App\Application;
use App\Commands\HandleEventsCommand;
use PHPUnit\Framework\TestCase;


class HandleEventsCommandTest extends TestCase {
  /**
   * @dataProvider eventProvider
   */
  public function testShouldEventBeRanReceiveEventDtoAndReturnCorrectBool(array $dto, bool $shouldEventBeRan) {
    $handleEventsCommand = new HandleEventsCommand(new Application(dirname(__DIR__)));
    $result = $handleEventsCommand->shouldEventBeRan($dto);
    self::assertEquals($shouldEventBeRan, $result);
  }

  public function eventProvider(): array {
    $currentMinute = (int) date('i'); // Текущая минута
    $currentHour = (int) date('H');   // Текущий час
    $currentDayOfWeek = (int) date('w'); // Текущий день

    return [
      'every minute' => [['minute' => '*', 'hour' => '*', 'day' => '*', 'month' => '*', 'day_of_week' => '*'], true],
      'at current minute' => [['minute' => (string)$currentMinute, 'hour' => '*', 'day' => '*', 'month' => '*', 'day_of_week' => '*'], true],
      'at 15 minutes past the hour' => [['minute' => '15', 'hour' => '*', 'day' => '*', 'month' => '*', 'day_of_week' => '*'], (15 === $currentMinute)],
      'at 20 minutes past the hour' => [['minute' => '20', 'hour' => '*', 'day' => '*', 'month' => '*', 'day_of_week' => '*'], (20 === $currentMinute)],
      'on current hour' => [['minute' => '*', 'hour' => (string)$currentHour, 'day' => '*', 'month' => '*', 'day_of_week' => '*'], true],
      'on Mondays' => [['minute' => '*', 'hour' => '*', 'day' => '*', 'month' => '*', 'day_of_week' => '1'], (1 === $currentDayOfWeek)],
      'on Sundays' => [['minute' => '*', 'hour' => '*', 'day' => '*', 'month' => '*', 'day_of_week' => '0'], (0 === $currentDayOfWeek)],
    ];
  }
}
