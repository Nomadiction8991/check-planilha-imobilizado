<?php

declare(strict_types=1);

namespace App\Sync;

/**
 * Canal entre o notebook e o servidor. Em produção é HTTP (HttpSyncTransport);
 * nos testes pode ser uma chamada direta, sem rede.
 */
interface SyncTransport
{
    /**
     * @param  list<array<string, mixed>>  $alteracoes
     * @return array<string, mixed>
     */
    public function enviar(array $alteracoes): array;

    /**
     * @return array{alteracoes: list<array<string, mixed>>, cursor: int, mais: bool}
     */
    public function receber(int $depois): array;
}
