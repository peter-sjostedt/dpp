<?php
namespace App\Config;

class Database {
    private static ?self $instance = null;
    private \PDO $pdo;

    private function __construct() {
        $this->pdo = new \PDO(
            'mysql:host=localhost;dbname=petersjo_dpp;charset=utf8mb4',
            'petersjo_hospitex',
            'k)6ZPqh%8jZNdPx+',
            [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
                \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
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
