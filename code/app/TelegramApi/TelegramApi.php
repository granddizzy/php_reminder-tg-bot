<?php

namespace App\TelegramApi;

class TelegramApi {
  private $apiUrl;
  private $token;

  public function __construct($token) {
    $this->token = $token;
    $this->apiUrl = "https://api.telegram.org/bot{$this->token}/";
  }

  public function getMessages(int $offset = 0, int $count = 20) {
    $url = $this->apiUrl . 'getUpdates?offset=' . $offset . '&limit=' . $count;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true)['result'];
  }

  public function sendMessage($chatId, $message) {
    $url = $this->apiUrl . 'sendMessage';
    $data = [
      'chat_id' => $chatId,
      'text' => $message,
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
  }
}