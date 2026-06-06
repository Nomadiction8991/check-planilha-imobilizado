<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

class ConnectionManager
{
    private static ?PDO $conexao = null;
    private static ?array $config = null;

    private static array $defaultOptions = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
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
            'database' => LerEnv::obter('DB_NAME', LerEnv::obter('DB_DATABASE', 'anvycomb_checkplanilha')),
            'username' => LerEnv::obter('DB_USER', LerEnv::obter('DB_USERNAME', 'anvycomb_checkplanilha')),
            'password' => LerEnv::obter('DB_PASS', LerEnv::obter('DB_PASSWORD', '')),
            'charset'  => LerEnv::obter('DB_CHARSET', 'utf8mb4'),
            'port'     => LerEnv::obter('DB_PORT', '3306'),
            'driver'   => LerEnv::obter('DB_CONNECTION', 'mysql'),
        ];
    }

    private static function buildConnection(): PDO
    {
        $config = self::resolveConfig();
        $hosts = self::resolveHostCandidates((string) $config['host']);
        $lastException = null;
        $driver = (string) ($config['driver'] ?? 'mysql');

        foreach ($hosts as $host) {
            $dsn = self::buildDsn($driver, $host, $config);

            try {
                $options = self::$defaultOptions;
                if ($driver === 'mysql') {
                    $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci";
                }

                return new PDO($dsn, $config['username'], $config['password'], $options);
            } catch (PDOException $exception) {
                $lastException = $exception;
            }
        }

        throw $lastException ?? new PDOException('Não foi possível conectar ao banco de dados.');
    }

    /**
     * @return list<string>
     */
    private static function resolveHostCandidates(string $host): array
    {
        $host = trim($host);
        if ($host === '') {
            return ['127.0.0.1'];
        }

        $candidates = [$host];
        if ($host === 'db') {
            $candidates[] = '127.0.0.1';
            $candidates[] = 'localhost';
        }

        return array_values(array_unique($candidates));
    }

    private static function buildDsn(string $driver, string $host, array $config): string
    {
        if ($driver === 'pgsql') {
            return sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $host,
                $config['port'],
                $config['database'],
            );
        }

        return sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $host,
            $config['port'],
            $config['database'],
            $config['charset'],
        );
    }
}
