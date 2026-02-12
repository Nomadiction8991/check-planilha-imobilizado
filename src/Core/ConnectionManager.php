<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * ConnectionManager - Gerenciador Centralizado de Conexões
 * 
 * SOLID Principles:
 * - Single Responsibility: Gerencia APENAS conexões de banco
 * - Open/Closed: Extensível via configuração, fechado para modificação
 * - Dependency Inversion: Retorna interface PDO, não implementação específica
 * 
 * @package App\Core
 */
class ConnectionManager
{
    private static ?PDO $conexao = null;
    private static ?array $config = null;

    /**
     * Configura parâmetros de conexão
     * 
     * @param array $config ['host', 'database', 'username', 'password', 'charset', 'port']
     */
    public static function configure(array $config): void
    {
        self::$config = $config;
        self::$conexao = null; // Reset conexão existente
    }

    /**
     * Retorna conexão PDO singleton
     * 
     * @return PDO
     * @throws PDOException Se conexão falhar
     */
    public static function getConnection(): PDO
    {
        if (self::$conexao instanceof PDO) {
            return self::$conexao;
        }

        if (self::$config === null) {
            // Fallback para variáveis de ambiente
            self::$config = [
                'host' => LerEnv::obter('DB_HOST', '127.0.0.1'),
                'database' => LerEnv::obter('DB_DATABASE', 'ellobackup'),
                'username' => LerEnv::obter('DB_USERNAME', 'root'),
                'password' => LerEnv::obter('DB_PASSWORD', ''),
                'charset' => LerEnv::obter('DB_CHARSET', 'utf8mb4'),
                'port' => LerEnv::obter('DB_PORT', '3306'),
            ];
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            self::$config['host'],
            self::$config['port'],
            self::$config['database'],
            self::$config['charset']
        );

        self::$conexao = new PDO($dsn, self::$config['username'], self::$config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ]);

        return self::$conexao;
    }

    /**
     * Cria NOVA conexão (não-singleton) para transações isoladas
     * 
     * @return PDO
     */
    public static function createNewConnection(): PDO
    {
        $config = self::$config ?? [
            'host' => LerEnv::obter('DB_HOST', '127.0.0.1'),
            'database' => LerEnv::obter('DB_DATABASE', 'ellobackup'),
            'username' => LerEnv::obter('DB_USERNAME', 'root'),
            'password' => LerEnv::obter('DB_PASSWORD', ''),
            'charset' => LerEnv::obter('DB_CHARSET', 'utf8mb4'),
            'port' => LerEnv::obter('DB_PORT', '3306'),
        ];

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        return new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    /**
     * Reseta conexão (útil para testes)
     */
    public static function reset(): void
    {
        self::$conexao = null;
        self::$config = null;
    }

    /**
     * Backward compatibility: retorna conexão para código legado ($conexao global)
     * 
     * @deprecated Use ConnectionManager::getConnection() diretamente
     */
    public static function getGlobalConnection(): PDO
    {
        return self::getConnection();
    }
}
