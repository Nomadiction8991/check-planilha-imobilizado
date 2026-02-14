<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

class ConnectionManager
{
    private static ?PDO $conexao = null;
    private static ?array $config = null;

    private static array $defaultOptions = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ];

    public static function configure(array $config): void
    {
        self::$config = $config;
        self::$conexao = null;
    }

    public static function getConnection(): PDO
    {
        if (self::$conexao instanceof PDO) {
            return self::$conexao;
        }

        self::$conexao = self::buildConnection();

        return self::$conexao;
    }

    public static function createNewConnection(): PDO
    {
        return self::buildConnection();
    }

    public static function reset(): void
    {
        self::$conexao = null;
        self::$config = null;
    }

    private static function resolveConfig(): array
    {
        return self::$config ?? [
            'host'     => LerEnv::obter('DB_HOST', '127.0.0.1'),
            'database' => LerEnv::obter('DB_NAME', LerEnv::obter('DB_DATABASE', 'ellobackup')),
            'username' => LerEnv::obter('DB_USER', LerEnv::obter('DB_USERNAME', 'root')),
            'password' => LerEnv::obter('DB_PASS', LerEnv::obter('DB_PASSWORD', '')),
            'charset'  => LerEnv::obter('DB_CHARSET', 'utf8mb4'),
            'port'     => LerEnv::obter('DB_PORT', '3306'),
        ];
    }

    private static function buildConnection(): PDO
    {
        $config = self::resolveConfig();

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        return new PDO($dsn, $config['username'], $config['password'], self::$defaultOptions);
    }
}
