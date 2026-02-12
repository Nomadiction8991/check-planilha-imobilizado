<?php

namespace App\Core;

/**
 * SessionManager - Gerenciador de Sessões
 * 
 * SOLID Principles:
 * - Single Responsibility: Gerencia APENAS sessões
 * - Open/Closed: Extensível via métodos, não modifica core
 * - Liskov Substitution: Pode ser substituído por SessionManagerInterface
 * 
 * @package App\Core
 */
class SessionManager
{
    private static bool $started = false;

    /**
     * Inicia sessão se não iniciada
     */
    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_start([
            'cookie_httponly' => true,
            'cookie_secure' => isset($_SERVER['HTTPS']),
            'cookie_samesite' => 'Strict'
        ]);

        self::$started = true;
    }

    /**
     * Define valor na sessão
     */
    public static function set(string $key, $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    /**
     * Recupera valor da sessão
     */
    public static function get(string $key, $default = null)
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Verifica se chave existe
     */
    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    /**
     * Remove chave da sessão
     */
    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    /**
     * Define flash message (mensagem única)
     */
    public static function flash(string $key, $value): void
    {
        self::start();
        $_SESSION['_flash'][$key] = $value;
    }

    /**
     * Recupera e remove flash message
     */
    public static function getFlash(string $key, $default = null)
    {
        self::start();
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    /**
     * Verifica se usuário está autenticado
     */
    public static function isAuthenticated(): bool
    {
        return self::has('usuario_id');
    }

    /**
     * Recupera ID do usuário autenticado
     */
    public static function getUserId(): ?int
    {
        $id = self::get('usuario_id');
        return $id ? (int)$id : null;
    }

    /**
     * Recupera nome do usuário autenticado
     */
    public static function getUserName(): ?string
    {
        return self::get('usuario_nome');
    }

    /**
     * Recupera email do usuário autenticado
     */
    public static function getUserEmail(): ?string
    {
        return self::get('usuario_email');
    }

    /**
     * Define dados do usuário autenticado
     */
    public static function setUser(int $id, string $nome, string $email): void
    {
        self::set('usuario_id', $id);
        self::set('usuario_nome', $nome);
        self::set('usuario_email', $email);
    }

    /**
     * Limpa dados do usuário (logout)
     */
    public static function clearUser(): void
    {
        self::remove('usuario_id');
        self::remove('usuario_nome');
        self::remove('usuario_email');
    }

    /**
     * Destrói sessão completamente
     */
    public static function destroy(): void
    {
        self::start();
        session_destroy();
        self::$started = false;
    }

    /**
     * Regenera ID da sessão (segurança contra session fixation)
     */
    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    /**
     * Retorna todos os dados da sessão
     */
    public static function all(): array
    {
        self::start();
        return $_SESSION;
    }

    /**
     * Limpa todos os dados da sessão (mantém sessão ativa)
     */
    public static function clear(): void
    {
        self::start();
        $_SESSION = [];
    }
}
