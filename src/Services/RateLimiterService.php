<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Serviço de Rate Limiting para proteção contra força bruta
 */
class RateLimiterService
{
    private const ATTEMPTS_KEY = 'rate_limit_attempts_';
    private const LAST_ATTEMPT_KEY = 'rate_limit_time_';
    private int $maxAttempts;
    private int $windowSeconds;

    public function __construct(int $maxAttempts = 5, int $windowSeconds = 900)
    {
        $this->maxAttempts = $maxAttempts;
        $this->windowSeconds = $windowSeconds; // 15 minutos padrão
    }

    /**
     * Verifica se o cliente excedeu o limite de tentativas
     */
    public function isRateLimited(string $identifier): bool
    {
        $key = self::ATTEMPTS_KEY . $identifier;
        $timeKey = self::LAST_ATTEMPT_KEY . $identifier;

        $attempts = $_SESSION[$key] ?? 0;
        $lastAttemptTime = $_SESSION[$timeKey] ?? 0;
        $now = time();

        // Se passou a janela de tempo, reseta
        if ($now - $lastAttemptTime > $this->windowSeconds) {
            unset($_SESSION[$key], $_SESSION[$timeKey]);
            return false;
        }

        return $attempts >= $this->maxAttempts;
    }

    /**
     * Registra uma tentativa falhada
     */
    public function recordFailedAttempt(string $identifier): void
    {
        $key = self::ATTEMPTS_KEY . $identifier;
        $timeKey = self::LAST_ATTEMPT_KEY . $identifier;

        $_SESSION[$key] = ($_SESSION[$key] ?? 0) + 1;
        $_SESSION[$timeKey] = time();
    }

    /**
     * Reseta tentativas para um identificador
     */
    public function reset(string $identifier): void
    {
        $key = self::ATTEMPTS_KEY . $identifier;
        $timeKey = self::LAST_ATTEMPT_KEY . $identifier;

        unset($_SESSION[$key], $_SESSION[$timeKey]);
    }

    /**
     * Obtém número de tentativas restantes
     */
    public function getRemainingAttempts(string $identifier): int
    {
        $key = self::ATTEMPTS_KEY . $identifier;
        $timeKey = self::LAST_ATTEMPT_KEY . $identifier;

        $attempts = $_SESSION[$key] ?? 0;
        $lastAttemptTime = $_SESSION[$timeKey] ?? 0;
        $now = time();

        // Se passou a janela de tempo, reseta
        if ($now - $lastAttemptTime > $this->windowSeconds) {
            return $this->maxAttempts;
        }

        return max(0, $this->maxAttempts - $attempts);
    }

    /**
     * Obtém tempo em segundos até reset automático
     */
    public function getResetTime(string $identifier): int
    {
        $timeKey = self::LAST_ATTEMPT_KEY . $identifier;
        $lastAttemptTime = $_SESSION[$timeKey] ?? 0;

        if ($lastAttemptTime === 0) {
            return 0;
        }

        $resetTime = $lastAttemptTime + $this->windowSeconds;
        $now = time();

        return max(0, $resetTime - $now);
    }
}
