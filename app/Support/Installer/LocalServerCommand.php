<?php

declare(strict_types=1);

namespace App\Support\Installer;

use InvalidArgumentException;

/**
 * Monta o comando que sobe o sistema no notebook com o servidor embutido do PHP.
 *
 * Usa "php -S" e não "artisan serve": o "artisan serve" repassa o .env ao processo filho,
 * e isso ignora as variáveis do ambiente (testado na fase 0).
 */
final class LocalServerCommand
{
    private const PORTA_MINIMA = 1024;

    private const PORTA_MAXIMA = 65535;

    /**
     * @return list<string>
     */
    public function argumentos(int $porta, string $host = '0.0.0.0'): array
    {
        if ($porta < self::PORTA_MINIMA || $porta > self::PORTA_MAXIMA) {
            throw new InvalidArgumentException(
                "Porta {$porta} inválida. Use um número entre " . self::PORTA_MINIMA . ' e ' . self::PORTA_MAXIMA . '.',
            );
        }

        return ['php', '-S', "{$host}:{$porta}", '-t', 'public', 'public/index.php'];
    }
}
