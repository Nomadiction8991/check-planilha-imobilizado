<?php

declare(strict_types=1);

namespace App\Support\Connection;

use InvalidArgumentException;

/**
 * Texto do QR code que faz o celular entrar na rede Wi-Fi do notebook.
 *
 * Formato padrão "WIFI:T:WPA;S:<rede>;P:<senha>;;", lido pela câmera do iPhone e da maioria
 * dos Androids. Nome e senha escapam \ ; , : e " com barra invertida.
 */
final class WifiQrPayload
{
    private const SENHA_MINIMA = 8;

    private const SENHA_MAXIMA = 63;

    public static function para(string $nomeDaRede, string $senha): string
    {
        $tamanho = strlen($senha);

        if ($tamanho < self::SENHA_MINIMA || $tamanho > self::SENHA_MAXIMA) {
            throw new InvalidArgumentException(
                'A senha da rede precisa ter entre ' . self::SENHA_MINIMA . ' e ' . self::SENHA_MAXIMA . ' caracteres.',
            );
        }

        return 'WIFI:T:WPA;S:' . self::escapar($nomeDaRede) . ';P:' . self::escapar($senha) . ';;';
    }

    private static function escapar(string $texto): string
    {
        return preg_replace('/([\\\\;,:"])/', '\\\\$1', $texto) ?? $texto;
    }
}
