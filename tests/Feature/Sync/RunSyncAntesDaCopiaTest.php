<?php

declare(strict_types=1);

namespace Tests\Feature\Sync;

use Illuminate\Support\Facades\Http;

/**
 * Antes da cópia inicial, a sincronização não roda: o registro de alterações não tem os dados
 * que existiam no servidor antes dos gatilhos, e eles só chegam pela cópia.
 */
class RunSyncAntesDaCopiaTest extends SincronizacaoTestCase
{
    public function test_sincronizacao_nao_roda_antes_da_copia_inicial(): void
    {
        config([
            'modo_igreja.servidor_url' => 'https://servidor.teste',
            'modo_igreja.aparelho_token' => 'codigo-de-teste',
        ]);
        Http::fake();

        $this->artisan('sincronizacao:executar')
            ->expectsOutputToContain('ainda não baixou a cópia')
            ->assertSuccessful();

        Http::assertNothingSent();
    }
}
