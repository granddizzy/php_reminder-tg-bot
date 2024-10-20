<?php

namespace App\Queue;

interface Queue {
  public function sendMessage($message): void;
  public function getMessage(): string | null;
  public function ackLastMessage(): void;
}