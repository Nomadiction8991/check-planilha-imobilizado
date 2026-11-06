<?php

declare(strict_types=1);

namespace App\Sync;

/**
 * Canal das planilhas entre o notebook e o servidor.
 */
interface FileTransport
{
    /**
     * @return list<array{nome: string, sha256: string, tamanho: int}>
     */
    public function listar(): array;

    /**
     * @throws ArquivoCorrompidoException quando o outro lado recusa o conteúdo
     */
    public function enviar(string $nome, string $conteudo, string $sha256): void;

    /**
     * @return array{conteudo: string, sha256: string}
     *
     * @throws ArquivoCorrompidoException quando o conteúdo recebido não confere com o SHA-256
     */
    public function baixar(string $nome): array;
}
