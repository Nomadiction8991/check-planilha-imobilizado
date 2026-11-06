<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Sync\Device;
use App\Sync\SyncService;
use App\Sync\SyncTransport;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Transporte de teste: chama o servidor no mesmo processo, usando o banco do servidor.
 * Faz o papel do HTTP sem sair do PHP, para o teste de ponta a ponta rodar com vários bancos.
 *
 * Se a chave do servidor for informada, as chamadas ao servidor rodam com ela, e não com
 * a chave do notebook que chamou. Assim cada lado usa a própria chave, como na vida real.
 */
final class LocalSyncTransport implements SyncTransport
{
    public function __construct(
        private readonly int $aparelhoId,
        private readonly string $conexaoServidor = 'servidor',
        private readonly ?string $chaveServidor = null,
    ) {
    }

    public function enviar(array $alteracoes): array
    {
        return $this->noServidor(function () use ($alteracoes): array {
            $aparelho = Device::query()->findOrFail($this->aparelhoId);

            return app(SyncService::class)->enviar($aparelho, $alteracoes);
        });
    }

    public function receber(int $depois): array
    {
        return $this->noServidor(function () use ($depois): array {
            $aparelho = Device::query()->findOrFail($this->aparelhoId);

            return app(SyncService::class)->receber($aparelho, $depois);
        });
    }

    /**
     * @template T
     *
     * @param  callable(): T  $acao
     * @return T
     */
    private function noServidor(callable $acao): mixed
    {
        $original = Crypt::getFacadeRoot();

        if ($this->chaveServidor !== null) {
            Crypt::swap(new Encrypter(base64_decode(substr($this->chaveServidor, 7)), 'AES-256-CBC'));
        }

        try {
            return DB::usingConnection($this->conexaoServidor, $acao);
        } finally {
            Crypt::swap($original);
        }
    }
}
