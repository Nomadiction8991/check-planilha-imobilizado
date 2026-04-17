<?php

declare(strict_types=1);

namespace App\Core;

class LerEnv
{
    private static ?array $variaveis = null;

    public static function obter(string $chave, mixed $padrao = null): mixed
    {
        if (self::$variaveis === null) {
            self::carregar();
        }

        return self::$variaveis[$chave] ?? $padrao;
    }

    private static function carregar(): void
    {
        $caminho = __DIR__ . '/../../.env';
        self::$variaveis = [];

        if (!file_exists($caminho)) {
            return;
        }

        $linhas = file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($linhas as $linha) {
            $linha = trim($linha);
            if ($linha === '' || str_starts_with($linha, '#')) {
                continue;
            }

            [$chave, $valor] = array_pad(explode('=', $linha, 2), 2, '');
            $valor = trim($valor);
            $valor = trim($valor, "\"'");
            self::$variaveis[trim($chave)] = $valor;
        }
    }
}
