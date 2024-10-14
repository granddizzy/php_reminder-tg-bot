<?php

namespace App\Commands;

use App\Application;
use App\Database\SQLite;
use App\EventSender\EventSender;
use App\Models\Event;

class HandleEventsCommand extends Command {

  protected Application $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  public function run(array $options = []): void {
    $event = new Event(new SQLite($this->app));
    $events = $event->select();
    $eventSender = new EventSender();
    foreach ($events as $event) {
      if ($this->shouldEventBeRan($event)) {
        $eventSender->sendMessage($this->app->env('TELEGRAM_TOKEN'), $event['receiver_id'], $event['text']);
      }
    }
  }

  public function shouldEventBeRan($event): bool {
    $currentMinute = (int)date("i");
    $currentHour = (int)date("H");
    $currentDay = (int)date("d");
    $currentMonth = (int)date("m");
    $currentWeekday = (int)date("w");

    return $this->matchesCronPart($event['minute'], $currentMinute) &&
      $this->matchesCronPart($event['hour'], $currentHour) &&
      $this->matchesCronPart($event['day'], $currentDay) &&
      $this->matchesCronPart($event['month'], $currentMonth) &&
      $this->matchesCronPart($event['day_of_week'], $currentWeekday);
  }

  private function matchesCronPart($cronPart, $currentValue): bool {
    // Если это звездочка, то любое значение подходит
    if ($cronPart === '*') {
      return true;
    }

    // Если это диапазон (например, 1-5)
    if (strpos($cronPart, '-') !== false) {
      [$start, $end] = explode('-', $cronPart);
      return $currentValue >= (int)$start && $currentValue <= (int)$end;
    }

    // Если это шаг (например, */5)
    if (strpos($cronPart, '*/') !== false) {
      $step = (int)substr($cronPart, 2);
      return $currentValue % $step === 0;
    }

    // Если это список значений через запятую (например, 1,2,3)
    if (strpos($cronPart, ',') !== false) {
      $values = explode(',', $cronPart);
      return in_array((string)$currentValue, $values, true);
    }

    // Если это конкретное значение
    return (int)$cronPart === $currentValue;
  }
}