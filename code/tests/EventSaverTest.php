<?php

namespace tests;

use App\Actions\EventSaver;
use App\Models\Event;
use PHPUnit\Framework\TestCase;

class EventSaverTest extends TestCase {
  /**
   * @dataProvider eventCorrectDataProvider
   */
  public function testHandleCallCorrectInsertInModel(array $dto, array $expected) {
    $eventModelMock = \Mockery::mock(Event::class);
    $eventModelMock->shouldReceive('insert')->with($expected)->once();

    $eventSaver = new EventSaver($eventModelMock);
    $eventSaver->handle($dto);
  }

  /**
   * @dataProvider eventIncorrectDataProvider
   * @skip
   */
  public function testHandleDoesNotCallInsertWithInvalidData(array $dto) {
    $eventModelMock = \Mockery::mock(Event::class);
    $eventModelMock->shouldNotReceive('insert');
    $eventSaver = new EventSaver($eventModelMock);
    $eventSaver->handle($dto);
  }

  public function eventCorrectDataProvider(): array {
    return [
      'full_event_dto' => [
        [
          'name' => 'Meeting',
          'text' => 'Discuss project updates',
          'receiver_id' => '12345',
          'minute' => '30',
          'hour' => '14',
          'day' => '15',
          'month' => '10',
          'day_of_week' => '2'
        ],
        // Добавьте ожидаемые значения
        [
          "name" => 'Meeting',
          "receiver_id" => '12345',
          "text" => 'Discuss project updates',
          "minute" => '30',
          "hour" => '14',
          "day" => '15',
          "month" => '10',
          "day_of_week" => '2'
        ]
      ],
      // Остальные тестовые данные
    ];
  }


  public function eventIncorrectDataProvider(): array {
    return [
      'missing_name' => [
        [
          'name' => '',
          'text' => 'Text without a name',
          'receiver_id' => '12345',
          'minute' => '30',
          'hour' => '14',
          'day' => '15',
          'month' => '10',
          'day_of_week' => '2'
        ],
      ],
      'invalid_receiver_id' => [
        [
          'name' => 'Event Name',
          'text' => 'Event description',
          'receiver_id' => 'invalid_id',
          'minute' => '30',
          'hour' => '14',
          'day' => '15',
          'month' => '10',
          'day_of_week' => '2'
        ],
      ],
      'out_of_range_minute' => [
        [
          'name' => 'Event with invalid minute',
          'text' => 'Description',
          'receiver_id' => '12345',
          'minute' => '60',  // минуты не могут быть больше 59
          'hour' => '14',
          'day' => '15',
          'month' => '10',
          'day_of_week' => '2'
        ],
      ],
      'hour_too_high' => [
        [
          'name' => 'Event with invalid hour',
          'text' => 'Description',
          'receiver_id' => '12345',
          'minute' => '30',
          'hour' => '25',  // часы не могут быть больше 23
          'day' => '15',
          'month' => '10',
          'day_of_week' => '2'
        ],
      ],
      'missing_day' => [
        [
          'name' => 'Event with missing day',
          'text' => 'Description',
          'receiver_id' => '12345',
          'minute' => '30',
          'hour' => '14',
          'day' => '',  // день отсутствует
          'month' => '10',
          'day_of_week' => '2'
        ],
      ],
    ];
  }

  public function tearDown(): void {
    \Mockery::close();
  }
}