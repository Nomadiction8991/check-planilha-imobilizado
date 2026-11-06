<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Sync\Device;
use App\Sync\SyncService;
use App\Sync\SyncTransport;
use Illuminate\Support\Facades\DB;

/**
 * Transporte de teste: chama o servidor no mesmo processo, usando o banco do servidor.
 * Faz o papel do HTTP sem sair do PHP, para o teste de ponta a ponta rodar com vários bancos.
 */
final class LocalSyncTransport implements SyncTransport
{
    public function __construct(
        private readonly int $aparelhoId,
        private readonly string $conexaoServidor = 'servidor',
    ) {
    }

    public function enviar(array $alteracoes): array
    {
        return DB::usingConnection($this->conexaoServidor, function () use ($alteracoes): array {
            $aparelho = Device::query()->findOrFail($this->aparelhoId);

            return app(SyncService::class)->enviar($aparelho, $alteracoes);
        });
    }

    public function receber(int $depois): array
    {
        return DB::usingConnection($this->conexaoServidor, function () use ($depois): array {
            $aparelho = Device::query()->findOrFail($this->aparelhoId);

            return app(SyncService::class)->receber($aparelho, $depois);
        });
    }
}
