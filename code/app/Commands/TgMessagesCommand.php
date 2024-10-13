<?php

namespace App\Commands;

use App\Application;
use App\TelegramApi\TelegramApi;

class TgMessagesCommand extends Command {
  private TelegramApi $telegramApi;
  protected Application $app;

  public function __construct(Application $app) {
    $this->app = $app;
    $this->telegramApi = new TelegramApi($app->env('TELEGRAM_TOKEN'));
  }

  function run(array $options = []): void {
    $offset = $options['offset'] ?? 0;

    $messages = $this->telegramApi->getMessages($offset);

    if (empty($messages)) {
      echo "Сообщений нет.\n";
    } else {
      foreach ($messages as $message) {
        echo 'Сообщение: ' . json_encode($message) . "\n";
      }
    }
  }
}