<?php
namespace App\Config;

class Database {
    private static ?self $instance = null;
    private \PDO $pdo;

    private function __construct() {
        $this->pdo = new \PDO(
            'mysql:host=localhost;dbname=dpp;charset=utf8mb4',
            'dpp_user',
            'dpp_password_123',
            [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): \PDO {
        return $this->pdo;
    }
}
