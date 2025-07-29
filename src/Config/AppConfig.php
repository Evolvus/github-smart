<?php

namespace App\Config;

class AppConfig
{
    private static $instance = null;
    private $config = [];

    private function __construct()
    {
        $this->loadEnvironmentVariables();
        $this->setDefaults();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadEnvironmentVariables(): void
    {
        if (file_exists(__DIR__ . '/../../.env')) {
            $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->load();
        }
    }

    private function setDefaults(): void
    {
        $this->config = [
            'app' => [
                'name' => $_ENV['APP_NAME'] ?? 'CRUX',
                'env' => $_ENV['APP_ENV'] ?? 'development',
                'debug' => $_ENV['APP_DEBUG'] ?? false,
            ],
            'database' => [
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'port' => $_ENV['DB_PORT'] ?? '3306',
                'name' => $_ENV['DB_NAME'] ?? 'project_management',
                'user' => $_ENV['DB_USER'] ?? 'root',
                'password' => $_ENV['DB_PASSWORD'] ?? '',
            ],
            'github' => [
                'token' => $_ENV['GITHUB_TOKEN'] ?? null,
                'org' => $_ENV['GITHUB_ORG'] ?? 'Syneca',
            ],
            'logging' => [
                'level' => $_ENV['LOG_LEVEL'] ?? 'INFO',
                'file' => $_ENV['LOG_FILE'] ?? 'app.log',
            ]
        ];
    }

    public function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    public function isProduction(): bool
    {
        return $this->get('app.env') === 'production';
    }

    public function isDebug(): bool
    {
        return $this->get('app.debug', false);
    }
} 