<?php

declare(strict_types=1);

namespace App\Core;

class Configuracoes
{
    private static ?array $dados = null;

    public static function pegar(string $chave): mixed
    {
        if (self::$dados === null) {
            self::$dados = require __DIR__ . '/../../config/app.php';
        }
        return self::$dados[$chave] ?? null;
    }

    public static function atualizar(string $chave, mixed $valor): bool
    {
        $file = __DIR__ . '/../../config/app.php';

        $config = @include $file;
        if (!is_array($config)) {
            return false;
        }

        // Whitelist allowed config keys
        $allowedKeys = ['titulo_site', 'project_root'];
        if (!in_array($chave, $allowedKeys, true)) {
            return false;
        }

        $config[$chave] = $valor;

        $linhas = ["<?php", "", "return ["];
        foreach ($config as $k => $v) {
            if (!is_string($k) || !preg_match('/^[a-zA-Z_]+$/', $k)) {
                continue;
            }
            if ($v === null) {
                $linhas[] = "    '{$k}' => null,";
            } elseif (is_string($v) && str_contains($v, 'dirname(')) {
                // Preserve code expressions like dirname(__DIR__)
                $linhas[] = "    '{$k}' => {$v},";
            } else {
                $safe = str_replace(["'", "\\"], ["\\'", "\\\\"], (string) $v);
                $linhas[] = "    '{$k}' => '{$safe}',";
            }
        }
        $linhas[] = "];";
        $linhas[] = "";

        $export = implode("\n", $linhas);

        $tmp = $file . '.tmp';
        $written = @file_put_contents($tmp, $export, LOCK_EX);
        if ($written === false) {
            @unlink($tmp);
            return false;
        }

        if (!@rename($tmp, $file)) {
            @unlink($tmp);
            return false;
        }

        self::$dados = null;
        return true;
    }
}
