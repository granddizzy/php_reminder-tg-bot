<?php

namespace tests;

use App\Application;
use App\Commands\SaveEventCommand;
use PHPUnit\Framework\TestCase;

class SaveEventCommandTest extends TestCase {
  /**
   * @dataProvider isNeedHelpDataProvider
   */
  public function testIsNeedHelp(array $options, bool $expected) {
    $handleSaveEventsCommand = new SaveEventCommand(new Application(dirname(__DIR__)));
    $result = $handleSaveEventsCommand->isNeedHelp($options);
    self::assertEquals($expected, $result);
  }

  public function isNeedHelpDataProvider() {
    return [
      'missing name' => [
        'options' => ['text' => 'Some text', 'receiver' => 'User', 'cron' => '*/5 * * * *'],
        'expected' => true,
      ],
      'missing text' => [
        'options' => ['name' => 'Task', 'receiver' => 'User', 'cron' => '*/5 * * * *'],
        'expected' => true,
      ],
      'missing receiver' => [
        'options' => ['name' => 'Task', 'text' => 'Some text', 'cron' => '*/5 * * * *'],
        'expected' => true,
      ],
      'missing cron' => [
        'options' => ['name' => 'Task', 'text' => 'Some text', 'receiver' => 'User'],
        'expected' => true,
      ],
      'help option set' => [
        'options' => ['name' => 'Task', 'text' => 'Some text', 'receiver' => 'User', 'cron' => '*/5 * * * *', 'help' => true],
        'expected' => true,
      ],
      'short help option set' => [
        'options' => ['name' => 'Task', 'text' => 'Some text', 'receiver' => 'User', 'cron' => '*/5 * * * *', 'h' => true],
        'expected' => true,
      ],
      'all options present' => [
        'options' => ['name' => 'Task', 'text' => 'Some text', 'receiver' => 'User', 'cron' => '*/5 * * * *'],
        'expected' => false,
      ],
    ];
  }
}