<?php

declare(strict_types=1);

namespace App\Sync;

/**
 * Canal do histórico de auditoria entre o notebook e o servidor.
 */
interface AuditTransport
{
    /**
     * @return list<array{sha256: string, linha: string}>
     */
    public function baixar(): array;

    /**
     * @param  list<array{sha256: string, linha: string}>  $entradas
     * @return int quantas linhas o servidor acrescentou
     */
    public function enviar(array $entradas): int;
}
