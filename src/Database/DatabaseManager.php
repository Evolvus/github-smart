<?php

namespace App\Database;

use PDO;
use PDOException;
use App\Config\AppConfig;

class DatabaseManager
{
    private static $instance = null;
    private $pdo = null;
    private $config;

    private function __construct()
    {
        $this->config = AppConfig::getInstance();
        $this->connect();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect(): void
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $this->config->get('database.host'),
                $this->config->get('database.port'),
                $this->config->get('database.name')
            );

            $this->pdo = new PDO(
                $dsn,
                $this->config->get('database.user'),
                $this->config->get('database.password'),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            );
        } catch (PDOException $e) {
            throw new DatabaseException("Database connection failed: " . $e->getMessage());
        }
    }

    public function getConnection(): PDO
    {
        if ($this->pdo === null) {
            $this->connect();
        }
        return $this->pdo;
    }

    public function query(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new DatabaseException("Query failed: " . $e->getMessage());
        }
    }

    public function queryOne(string $sql, array $params = []): ?array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            throw new DatabaseException("Query failed: " . $e->getMessage());
        }
    }

    public function execute(string $sql, array $params = []): int
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new DatabaseException("Execute failed: " . $e->getMessage());
        }
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollback(): void
    {
        $this->pdo->rollback();
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }
}

class DatabaseException extends \Exception
{
    // Custom database exception
} 