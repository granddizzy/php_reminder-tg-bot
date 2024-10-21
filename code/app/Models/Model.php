<?php

namespace App\Models;

use App\Database\Db;
use PDO;

abstract class Model {
  protected string $table = '';
  private Db $connector;

  public function __construct(Db $connector) {
    $this->connector = $connector;
  }

  public function execute(string $sql): void {
    $this->connector->query($sql)->execute();
  }

  public function insert(array $params): void {
    $columns = implode(', ', array_keys($params));

    $placeholders = implode(', ', array_map(fn($col) => ":$col", array_keys($params)));

    $query = "INSERT INTO {$this->table} ({$columns}) VALUES ($placeholders)";

    $conn = $this->connector->prepare($query);
    $conn->execute($params);
  }

  public function select(string $conditions = ''): array {
    $result = [];

    $pdo = $this->connector->query("SELECT * FROM {$this->table} {$conditions}");
    $pdo->setFetchMode(PDO::FETCH_ASSOC);

    while ($row = $pdo->fetch()) {
      $result[] = $row;
    }

    return $result;
  }
}