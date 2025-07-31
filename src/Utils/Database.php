<?php
namespace App\Utils;

use PDO;
use PDOException;
use App\Utils\Logger;

class Database
{
    public static function getPDOConnection(): ?PDO
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s',
                $_ENV['DB_HOST'],
                $_ENV['DB_PORT'],
                $_ENV['DB_NAME']
            );
            $con = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASSWORD']);
            $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $con;
        } catch (PDOException $e) {
            Logger::logError('PDO Database connection failed: ' . $e->getMessage(), 'DB_PDO_ERROR', [
                'host' => $_ENV['DB_HOST'],
                'database' => $_ENV['DB_NAME'],
                'error_code' => $e->getCode()
            ]);
            return null;
        }
    }

    public static function getConnection()
    {
        $con = mysqli_connect(
            $_ENV['DB_HOST'],
            $_ENV['DB_USER'],
            $_ENV['DB_PASSWORD'],
            $_ENV['DB_NAME'],
            $_ENV['DB_PORT']
        );
        if (mysqli_connect_errno()) {
            Logger::logError('Database connection failed: ' . mysqli_connect_error(), 'DB_ERROR', [
                'host' => $_ENV['DB_HOST'],
                'database' => $_ENV['DB_NAME'],
                'port' => $_ENV['DB_PORT'],
                'error' => mysqli_connect_error()
            ]);
            return false;
        }
        return $con;
    }
} 