<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Sync\AuditLog;
use App\Sync\AuditTransport;

/**
 * Canal de auditoria de teste: fala direto com o arquivo do "servidor", sem HTTP.
 */
final class LocalAuditTransport implements AuditTransport
{
    public function __construct(
        private readonly AuditLog $servidor,
    ) {
    }

    public function baixar(): array
    {
        return $this->servidor->linhas();
    }

    public function enviar(array $entradas): int
    {
        return $this->servidor->acrescentar($entradas);
    }
}
