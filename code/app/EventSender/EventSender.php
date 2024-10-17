<?php

namespace App\EventSender;

class EventSender {
  protected $telegramApi;

  public function sendMessage(string $receiver, string $message) {
    $this->telegramApi->sendMessage($receiver, date('d.m.y H:i') . " " . $message);
  }

  public function __construct($telegramApi) {
    $this->telegramApi = $telegramApi;
  }
}