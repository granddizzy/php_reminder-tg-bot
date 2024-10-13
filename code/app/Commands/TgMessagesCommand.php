<?php

namespace App\Commands;

use App\Application;
use App\Models\Event;
use App\TelegramApi\TelegramApi;

class TgMessagesCommand extends Command {
  private TelegramApi $telegramApi;
  protected Application $app;
  private array $userState = []; // Храним состояние пользователя
  private array $messageHistory = [];// Храним историю сообщений
  private const OFFSET_FILE = __DIR__ . '/offset.txt';

  public function __construct(Application $app) {
    $this->app = $app;
    $this->telegramApi = new TelegramApi($app->env('TELEGRAM_TOKEN'));
  }

  function run(array $options = []): void {
    $offset = $this->loadOffset();

    $messages = $this->telegramApi->getMessages($offset);

    if (!empty($messages)) {
      foreach ($messages as $message) {
        $this->processMessage($message);
        $offset = $message['update_id'] + 1;
      }
      $this->saveOffset($offset);
    }
  }

  private function processMessage($message): void {
    if (!isset($message['message']) || !isset($message['message']['text'])) {
      return;
    }

    $chatId = $message['message']['chat']['id'];
    $text = trim($message['message']['text']);

    $this->saveMessageHistory($chatId, $text);

    // Инициализируем состояние пользователя, если оно не существует
    if (!isset($this->userState[$chatId])) {
      $this->userState[$chatId] = [
        'operationType' => null,
        'step' => 0,
        'eventData' => []
      ];
    }

    if ($text === '/addEvent') {
      $this->userState[$chatId]['operationType'] = 'addEvent'; // Устанавливаем тип операции
      $this->sendMessage($chatId, "Укажите название события.");
      $this->userState[$chatId]['step'] = 1;
      return;
    }

    switch ($this->userState[$chatId]['operationType']) {
      case 'addEvent':
        $this->handleAddEvent($chatId, $text);
        break;

      default:
        $this->sendMessage($chatId, "Неизвестная команда.");
        break;
    }
  }

  private function handleAddEvent($chatId, $text): void {
    switch ($this->userState[$chatId]['step']) {
      case 1:
        $this->userState[$chatId]['eventData']['name'] = $text;
        $this->sendMessage($chatId, "Укажите ID пользователя.");
        $this->userState[$chatId]['step'] = 2;
        break;

      case 2:
        $this->userState[$chatId]['eventData']['receiver'] = $text;
        $this->sendMessage($chatId, "Введите текст напоминания.");
        $this->userState[$chatId]['step'] = 3;
        break;

      case 3:
        $this->userState[$chatId]['eventData']['text'] = $text;
        $this->sendMessage($chatId, "Введите cron расписание (например, * * * * * для каждую минуту).");
        $this->userState[$chatId]['step'] = 4;
        break;

      case 4:
        // Разбиваем cron-расписание на части
        $cronValues = explode(' ', $text);
        if (count($cronValues) !== 5) {
          $this->sendMessage($chatId, "Ошибка: неверный формат cron расписания.");
          return;
        }
        $this->userState[$chatId]['eventData']['cron'] = $text;

        $this->saveEvent($this->userState[$chatId]['eventData']);
        $this->sendMessage($chatId, "Я записал Ваше событие. Для нового события введите /addEvent.");
        unset($this->userState[$chatId]); // Сбрасываем состояние пользователя
        break;

      default:
        $this->sendMessage($chatId, "Неизвестная команда.");
        break;
    }
  }

  private function sendMessage($chatId, $text): void {
    $this->telegramApi->sendMessage($chatId, $text);
  }

  private function saveEvent(array $eventData): void {
    $event = new SaveEventCommand($this->app);
    $event->run($eventData);
  }

  private function saveMessageHistory($chatId, $text): void {
    if (!isset($this->messageHistory[$chatId])) {
      $this->messageHistory[$chatId] = [];
    }
    $this->messageHistory[$chatId][] = $text;
  }

  private function loadOffset(): int {
    if (file_exists(self::OFFSET_FILE)) {
      return (int)file_get_contents(self::OFFSET_FILE);
    }
    return 0;
  }

  private function saveOffset(int $offset): void {
    file_put_contents(self::OFFSET_FILE, (string)$offset);
  }
}