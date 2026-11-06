<?php

declare(strict_types=1);

namespace App\Support\Modo;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

final class EstadoModo
{
    private readonly string $caminho;

    public function __construct(?string $caminho = null)
    {
        $this->caminho = $caminho ?? storage_path('app/modo-igreja/estado.json');
    }

    public function estaOnline(): bool
    {
        return $this->ler()['modo'] === 'online';
    }

    public function desde(): ?string
    {
        return $this->ler()['desde'];
    }

    public function registrarResultado(bool $servidorRespondeu): void
    {
        File::ensureDirectoryExists(dirname($this->caminho));
        $trava = fopen($this->caminho.'.lock', 'c');
        if ($trava === false || ! flock($trava, LOCK_EX)) {
            throw new \RuntimeException('Não foi possível bloquear o estado do modo igreja.');
        }

        try {
            $estado = $this->ler();
            $estado['acertos'] = $servidorRespondeu ? $estado['acertos'] + 1 : 0;
            $estado['falhas'] = $servidorRespondeu ? 0 : $estado['falhas'] + 1;
            if (($estado['modo'] === 'online' && $estado['falhas'] >= 2)
                || ($estado['modo'] === 'offline' && $estado['acertos'] >= 2)) {
                $estado['modo'] = $servidorRespondeu ? 'online' : 'offline';
                $estado['desde'] = now()->toIso8601String();
            }
            File::replace($this->caminho, json_encode($estado, JSON_THROW_ON_ERROR));
        } finally {
            flock($trava, LOCK_UN);
            fclose($trava);
        }
    }

    /** @return array{modo: string, acertos: int, falhas: int, desde: ?string} */
    private function ler(): array
    {
        $padrao = ['modo' => 'online', 'acertos' => 0, 'falhas' => 0, 'desde' => null];
        if (! is_file($this->caminho)) {
            return $padrao;
        }

        try {
            $dados = json_decode((string) file_get_contents($this->caminho), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $dados = null;
        }

        // Arquivo com defeito: volta ao padrão, e a próxima verificação regrava o arquivo.
        if (! is_array($dados) || ! in_array($dados['modo'] ?? null, ['online', 'offline'], true)
            || ! is_int($dados['acertos'] ?? null) || ! is_int($dados['falhas'] ?? null)
            || (! is_string($dados['desde'] ?? null) && ($dados['desde'] ?? null) !== null)) {
            Log::warning('O estado do modo igreja está inválido. Usando o modo online até a próxima verificação.', ['arquivo' => $this->caminho]);

            return $padrao;
        }

        return ['modo' => $dados['modo'], 'acertos' => $dados['acertos'], 'falhas' => $dados['falhas'], 'desde' => $dados['desde'] ?? null];
    }
}
