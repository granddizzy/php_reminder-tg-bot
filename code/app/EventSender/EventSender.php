<?php

namespace App\EventSender;

use App\TelegramApi\TelegramApi;

class EventSender {
  public function sendMessage(string $token, string $receiver, string $message) {
    $tg = new TelegramApi($token);
    $tg->sendMessage($receiver, date('d.m.y H:i') . " " . $message);
  }
}