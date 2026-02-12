<?php

namespace App\Core;


class SessionManager
{
    private static bool $started = false;

    
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

    
    public static function set(string $key, $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    
    public static function get(string $key, $default = null)
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    
    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    
    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    
    public static function flash(string $key, $value): void
    {
        self::start();
        $_SESSION['_flash'][$key] = $value;
    }

    
    public static function getFlash(string $key, $default = null)
    {
        self::start();
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    
    public static function isAuthenticated(): bool
    {
        return self::has('usuario_id');
    }

    
    public static function getUserId(): ?int
    {
        $id = self::get('usuario_id');
        return $id ? (int)$id : null;
    }

    
    public static function getUserName(): ?string
    {
        return self::get('usuario_nome');
    }

    
    public static function getUserEmail(): ?string
    {
        return self::get('usuario_email');
    }

    
    public static function setUser(int $id, string $nome, string $email): void
    {
        self::set('usuario_id', $id);
        self::set('usuario_nome', $nome);
        self::set('usuario_email', $email);
    }

    
    public static function clearUser(): void
    {
        self::remove('usuario_id');
        self::remove('usuario_nome');
        self::remove('usuario_email');
    }

    
    public static function destroy(): void
    {
        self::start();
        session_destroy();
        self::$started = false;
    }

    
    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    
    public static function all(): array
    {
        self::start();
        return $_SESSION;
    }

    
    public static function clear(): void
    {
        self::start();
        $_SESSION = [];
    }
}
