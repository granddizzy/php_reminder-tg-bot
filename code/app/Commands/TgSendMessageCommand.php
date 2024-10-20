<?php

namespace App\Commands;

use App\Application;
use App\EventSender\EventSender;
use App\Queue\RabbitMQ;
use App\TelegramApi\TelegramApi;

class TgSendMessageCommand extends Command {
  protected Application $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  function run(array $options = []): void {
    $options = $this->getGetoptOptionValues();
    $queue = new RabbitMQ('eventSender');
    $eventSender = new EventSender(new TelegramApi($this->app->env('TELEGRAM_TOKEN')), $queue);
    $eventSender->sendMessage($options['receiver'], $options['text']);
  }

  private function getGetoptOptionValues(): array {
    $shortopts = 'c:h:';

    $longopts = [
      "receiver:",
      "text:",
    ];

    return getopt($shortopts, $longopts);
  }
}