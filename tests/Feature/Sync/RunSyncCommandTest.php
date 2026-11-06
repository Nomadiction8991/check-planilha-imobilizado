<?php

declare(strict_types=1);

namespace Tests\Feature\Sync;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * O comando de sincronização precisa de endereço e código, e sem internet não pode quebrar.
 */
class RunSyncCommandTest extends SincronizacaoTestCase
{
    public function test_recusa_sincronizar_sem_endereco_e_codigo(): void
    {
        config(['modo_igreja.servidor_url' => null, 'modo_igreja.aparelho_token' => null]);

        $this->artisan('sincronizacao:executar')
            ->expectsOutputToContain('MODO_IGREJA_SERVIDOR_URL')
            ->assertExitCode(1);
    }

    public function test_sem_conexao_avisa_e_nao_perde_nada(): void
    {
        config([
            'modo_igreja.servidor_url' => 'https://servidor.teste',
            'modo_igreja.aparelho_token' => 'codigo-de-teste',
        ]);

        Http::fake(function (): never {
            throw new ConnectionException('Sem internet.');
        });

        $this->artisan('sincronizacao:executar')
            ->expectsOutputToContain('Sem conexão')
            ->assertSuccessful();
    }
}
