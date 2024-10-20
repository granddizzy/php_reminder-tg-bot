<?php

namespace App\Commands;

use App\Application;
use App\Queue\RabbitMQ;

class QueueManagerCommand extends Command {
  protected Application $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  function run(array $options = []): void {
    $queue = new RabbitMQ('eventSender');
    while (true) {
      $message = $queue->getMessage();

      if ($message) {
        $class = unserialize($message);
        $class->handle();
        $queue->ackLastMessage();
      }

      sleep(10);
    }
  }
}