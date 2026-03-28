<?php

declare(strict_types=1);

namespace App\Core;

use DateTime;

/**
 * Logger — Logging estruturado com níveis e contexto.
 *
 * Implementa padrão de logger centralizado com suporte a:
 * - Múltiplos níveis: DEBUG, INFO, WARN, ERROR, FATAL
 * - Contexto estruturado (user_id, ação, timestamps)
 * - Stack traces para exceções
 * - Formatação estruturada (JSON/text)
 *
 * Exemplo:
 *   Logger::info('Importação iniciada', ['user_id' => 1, 'file' => 'produtos.csv']);
 *   Logger::error('Erro ao processar', ['erro' => $ex->getMessage()]);
 *
 * @since 12.0
 */
class Logger
{
    /** Níveis de logging */
    public const DEBUG = 'DEBUG';
    public const INFO = 'INFO';
    public const WARN = 'WARN';
    public const ERROR = 'ERROR';
    public const FATAL = 'FATAL';

    /** @var string Caminho do arquivo de log */
    private static string $logFile = '';

    /** @var int Nível mínimo para registrar */
    private static int $minLevel = 0;

    /** Mapeamento de nível para prioridade (0=DEBUG, 4=FATAL) */
    private static array $levelMap = [
        self::DEBUG => 0,
        self::INFO  => 1,
        self::WARN  => 2,
        self::ERROR => 3,
        self::FATAL => 4,
    ];

    /**
     * Inicializa o logger.
     *
     * @param string $logPath Caminho do arquivo de log
     * @param string $minLevel Nível mínimo (DEBUG, INFO, WARN, ERROR, FATAL)
     * @return void
     */
    public static function initialize(string $logPath, string $minLevel = self::INFO): void
    {
        self::$logFile = $logPath;
        self::$minLevel = self::$levelMap[$minLevel] ?? 1;

        // Criar diretório se não existir
        $dir = dirname($logPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * Registra mensagem de DEBUG (mais verboso).
     *
     * @param string $mensagem Mensagem a registrar
     * @param array $contexto Contexto adicional
     * @return void
     */
    public static function debug(string $mensagem, array $contexto = []): void
    {
        self::log(self::DEBUG, $mensagem, $contexto);
    }

    /**
     * Registra mensagem de INFO (informações gerais).
     *
     * @param string $mensagem Mensagem a registrar
     * @param array $contexto Contexto adicional
     * @return void
     */
    public static function info(string $mensagem, array $contexto = []): void
    {
        self::log(self::INFO, $mensagem, $contexto);
    }

    /**
     * Registra mensagem de WARN (aviso).
     *
     * @param string $mensagem Mensagem a registrar
     * @param array $contexto Contexto adicional
     * @return void
     */
    public static function warn(string $mensagem, array $contexto = []): void
    {
        self::log(self::WARN, $mensagem, $contexto);
    }

    /**
     * Registra mensagem de ERROR (erro).
     *
     * @param string $mensagem Mensagem a registrar
     * @param array $contexto Contexto adicional (inclui 'exceção' se aplicável)
     * @return void
     */
    public static function error(string $mensagem, array $contexto = []): void
    {
        self::log(self::ERROR, $mensagem, $contexto);
    }

    /**
     * Registra mensagem de FATAL (erro crítico).
     *
     * @param string $mensagem Mensagem a registrar
     * @param array $contexto Contexto adicional
     * @return void
     */
    public static function fatal(string $mensagem, array $contexto = []): void
    {
        self::log(self::FATAL, $mensagem, $contexto);
    }

    /**
     * Registra exceção com stack trace.
     *
     * @param \Throwable $exception Exceção a registrar
     * @param string $mensagem Mensagem contextual
     * @param array $contexto Contexto adicional
     * @return void
     */
    public static function exception(\Throwable $exception, string $mensagem = '', array $contexto = []): void
    {
        $contexto['exceção_classe'] = get_class($exception);
        $contexto['exceção_código'] = $exception->getCode();
        $contexto['exceção_mensagem'] = $exception->getMessage();
        $contexto['exceção_arquivo'] = $exception->getFile();
        $contexto['exceção_linha'] = $exception->getLine();
        $contexto['stack_trace'] = self::formatarStackTrace($exception);

        $msg = $mensagem ?: $exception->getMessage();
        self::error($msg, $contexto);
    }

    /**
     * Registra mensagem com nível especificado.
     *
     * @param string $nivel DEBUG, INFO, WARN, ERROR, FATAL
     * @param string $mensagem Mensagem a registrar
     * @param array $contexto Contexto estruturado
     * @return void
     */
    private static function log(string $nivel, string $mensagem, array $contexto = []): void
    {
        // Verificar se deve registrar baseado no nível mínimo
        if ((self::$levelMap[$nivel] ?? -1) < self::$minLevel) {
            return;
        }

        $timestamp = (new DateTime())->format('Y-m-d H:i:s.u');
        $logEntry = self::formatarEntrada($timestamp, $nivel, $mensagem, $contexto);

        // Registrar em arquivo
        if (self::$logFile) {
            self::escreverArquivo($logEntry);
        }

        // Também registrar em error_log para fallback
        error_log($logEntry);
    }

    /**
     * Formata entrada de log estruturada.
     *
     * @param string $timestamp Timestamp ISO8601
     * @param string $nivel DEBUG, INFO, WARN, ERROR, FATAL
     * @param string $mensagem Mensagem
     * @param array $contexto Contexto
     * @return string Entrada formatada
     */
    private static function formatarEntrada(
        string $timestamp,
        string $nivel,
        string $mensagem,
        array $contexto = []
    ): string {
        // Contexto básico
        $entrada = [
            'timestamp' => $timestamp,
            'nivel' => $nivel,
            'mensagem' => $mensagem,
        ];

        // Adicionar contexto de sessão se disponível
        if (class_exists(\App\Core\SessionManager::class)) {
            $userId = \App\Core\SessionManager::getUserId();
            if ($userId) {
                $entrada['user_id'] = $userId;
            }
        }

        // Adicionar contexto fornecido
        if (!empty($contexto)) {
            $entrada['contexto'] = $contexto;
        }

        // Adicionar informações de request
        if (!empty($_SERVER['REQUEST_METHOD'])) {
            $entrada['request'] = [
                'método' => $_SERVER['REQUEST_METHOD'],
                'path' => $_SERVER['REQUEST_URI'] ?? '',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'desconhecido',
            ];
        }

        // Formatar como JSON para estrutura
        return json_encode($entrada, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }

    /**
     * Formata stack trace de exceção.
     *
     * @param \Throwable $exception Exceção
     * @return array Stack trace formatado
     */
    private static function formatarStackTrace(\Throwable $exception): array
    {
        $trace = [];
        $counter = 0;

        foreach ($exception->getTrace() as $frame) {
            $trace[] = [
                'frame' => ++$counter,
                'arquivo' => $frame['file'] ?? 'unknown',
                'linha' => $frame['line'] ?? 0,
                'função' => $frame['function'] ?? 'unknown',
                'classe' => $frame['class'] ?? '',
            ];
        }

        return $trace;
    }

    /**
     * Escreve entrada no arquivo de log.
     *
     * @param string $entrada Entrada formatada
     * @return void
     */
    private static function escreverArquivo(string $entrada): void
    {
        if (!self::$logFile) {
            return;
        }

        $dir = dirname(self::$logFile);
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            return;
        }

        // Usar file_put_contents com lock para evitar corrupção
        $flags = FILE_APPEND | LOCK_EX;
        @file_put_contents(self::$logFile, $entrada, $flags);
    }

    /**
     * Obtém caminho do arquivo de log atual.
     *
     * @return string
     */
    public static function getLogFile(): string
    {
        return self::$logFile;
    }

    /**
     * Define nível mínimo de logging.
     *
     * @param string $nivel DEBUG, INFO, WARN, ERROR, FATAL
     * @return void
     */
    public static function setMinLevel(string $nivel): void
    {
        self::$minLevel = self::$levelMap[$nivel] ?? 1;
    }
}
