<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Sync\FileTransport;
use App\Sync\SpreadsheetStore;

/**
 * Canal de planilhas de teste: fala direto com a pasta do "servidor", sem HTTP.
 * Conta os envios e os downloads para o teste conferir que nada foi repetido.
 */
final class LocalFileTransport implements FileTransport
{
    public int $envios = 0;

    public int $downloads = 0;

    public function __construct(
        private readonly SpreadsheetStore $servidor,
    ) {
    }

    public function listar(): array
    {
        return $this->servidor->listar();
    }

    public function enviar(string $nome, string $conteudo, string $sha256): void
    {
        $this->envios++;
        $this->servidor->gravar($nome, $conteudo, $sha256);
    }

    public function baixar(string $nome): array
    {
        $this->downloads++;

        return $this->servidor->ler($nome);
    }
}
