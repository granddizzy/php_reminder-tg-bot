<?php

namespace App\Commands;

use App\Application;
use App\Cache\Redis;
use App\TelegramApi\TelegramApi;
use Predis\Client as PredisClient;

class TgMessagesCommand extends Command {
  private TelegramApi $telegramApi;
  protected Application $app;
  private array $userState = []; // Храним состояние пользователя
  private Redis $redis;

  public function __construct(Application $app) {
    $this->app = $app;
    $this->telegramApi = new TelegramApi($app->env('TELEGRAM_TOKEN'));

    // Инициализация клиента Predis
    $predisClient = new PredisClient([
      'host' => $app->env('REDIS_HOST', 'redis'),
      'port' => $app->env('REDIS_PORT', 6379),
    ]);
    $this->redis = new Redis($predisClient);
  }

  public function run(array $options = []): void {
    $this->receiveNewMessages();
  }

  private function receiveNewMessages() {
    $offset = $this->loadOffset();
    $messages = $this->telegramApi->getMessages($offset);

    if (!empty($messages)) {
      foreach ($messages as $message) {
        if (!isset($message['message']) || !isset($message['message']['text'])) {
          continue;
        }

        $chatId = $message['message']['chat']['id'];
        // Сохраняем сообщение в Redis
        $this->cacheMessage($chatId, $message);

        if ($offset > 0) $this->processMessage($chatId, $message);
        $this->saveOffset($message['update_id'] + 1);
      }
    }
  }

  private function processMessage($chatId, $message): void {
    $text = trim($message['message']['text']);

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

  private function cacheMessage($chatId, $message): void {
    // Уникальный ключ для кэширования сообщения
    $messageId = $message['message']['message_id'];
    $key = "telegram_message:{$chatId}:{$messageId}";

    // Сохраняем сообщение в Redis с временем жизни (TTL) 1 час
    $this->redis->set($key, json_encode($message), 3600);
  }

  private function getCachedMessage($chatId, $messageId) {
    $key = "telegram_message:{$chatId}:{$messageId}";

    // Получаем сообщение из Redis
    $cachedMessage = $this->redis->get($key);
    return $cachedMessage ? json_decode($cachedMessage, true) : null;
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

  private function loadOffset(): int {
    return $this->redis->get('tg_messages_offset', 0);
  }

  private function saveOffset(int $offset): void {
    $this->redis->set('tg_messages_offset', $offset ?? 0);
  }
}