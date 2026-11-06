<?php

declare(strict_types=1);

namespace App\Support\Connection;

use Illuminate\Support\Str;
use RuntimeException;

/**
 * Senha da rede do ponto de acesso. É criada na primeira vez e fica num arquivo só do dono (0600).
 */
final class HotspotCredentials
{
    private const TAMANHO_DA_SENHA = 12;

    public function __construct(
        private readonly string $arquivo,
    ) {
    }

    public function senha(): string
    {
        if (is_file($this->arquivo)) {
            $senha = trim((string) file_get_contents($this->arquivo));

            if ($senha !== '') {
                return $senha;
            }
        }

        $senha = Str::random(self::TAMANHO_DA_SENHA);
        $this->gravar($senha);

        return $senha;
    }

    private function gravar(string $senha): void
    {
        $pasta = dirname($this->arquivo);

        if (! is_dir($pasta) && ! mkdir($pasta, 0700, true) && ! is_dir($pasta)) {
            throw new RuntimeException("Não foi possível criar a pasta {$pasta} para guardar a senha da rede.");
        }

        if (file_put_contents($this->arquivo, $senha, LOCK_EX) === false) {
            throw new RuntimeException('Não foi possível guardar a senha da rede. Confira as permissões da pasta de dados.');
        }

        chmod($this->arquivo, 0600);
    }
}
