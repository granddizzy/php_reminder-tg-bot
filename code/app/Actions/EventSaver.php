<?php

namespace App\Actions;

use App\Models\Event;

class EventSaver {
  public function __construct(private Event $event) { }

  public function handle(array $eventDto): void {
    if ($this->isValid($eventDto)) {
      $this->saveEvent($eventDto);
    } else {
      throw new \InvalidArgumentException('Invalid event data provided.');
    }
  }

  private function saveEvent(array $params): void {
    $this->event->insert($params);
  }

  private function isValid(array $data): bool {
    return !empty($data['name']) &&
      $this->isValidReceiverId($data['receiver_id']) &&
      $this->isValidCronField($data['minute'], 0, 59) &&
      $this->isValidCronField($data['hour'], 0, 23) &&
      $this->isValidCronField($data['day'], 1, 31) &&
      $this->isValidCronField($data['month'], 1, 12) &&
      $this->isValidCronField($data['day_of_week'], 0, 6);
  }

  private function isValidReceiverId(string $receiverId): bool {
    return !empty($receiverId) && is_numeric($receiverId); // Проверка на пустоту и тип
  }

  private function isValidCronField(string $value, int $min, int $max): bool {
    // Проверяем на пустое значение
    if (empty($value)) {
      return false;
    }

    // Разрешаем значение "*" (означает "любое значение")
    if ($value === '*') {
      return true;
    }

    // Разрешаем диапазоны, например, "1-5"
    if (preg_match('/^\d+\-\d+$/', $value)) {
      [$start, $end] = explode('-', $value);
      return $start >= $min && $end <= $max && $start <= $end;
    }

    // Разрешаем списки значений, например, "0,15,30,45"
    if (preg_match('/^(\d+,)*\d+$/', $value)) {
      $values = explode(',', $value);
      foreach ($values as $v) {
        if ($v < $min || $v > $max) {
          return false;
        }
      }
      return true;
    }

    // Проверка на одно число
    return is_numeric($value) && $value >= $min && $value <= $max;
  }
}
