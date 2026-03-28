<?php

declare(strict_types=1);

namespace App\Core;

class CsrfService
{
    private const TOKEN_KEY = '_csrf_token';
    private const TOKEN_LENGTH = 32;

    public static function generateToken(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            SessionManager::start();
        }

        if (empty($_SESSION[self::TOKEN_KEY])) {
            $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(self::TOKEN_LENGTH));
        }

        return $_SESSION[self::TOKEN_KEY];
    }

    public static function getToken(): string
    {
        return self::generateToken();
    }

    public static function validate(?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        $sessionToken = $_SESSION[self::TOKEN_KEY] ?? '';

        if ($sessionToken === '') {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    public static function regenerate(): string
    {
        unset($_SESSION[self::TOKEN_KEY]);
        return self::generateToken();
    }

    public static function hiddenField(): string
    {
        $token = htmlspecialchars(self::getToken(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="_csrf_token" value="' . $token . '">';
    }
}
