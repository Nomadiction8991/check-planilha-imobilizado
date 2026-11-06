<?php

declare(strict_types=1);

namespace Tests\Feature\Sync;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Duas sincronizações ao mesmo tempo (agendada e manual) não podem rodar juntas no mesmo computador.
 */
class RunSyncTravaTest extends SincronizacaoTestCase
{
    public function test_segunda_sincronizacao_ao_mesmo_tempo_e_pulada_sem_falar_com_o_servidor(): void
    {
        config([
            'modo_igreja.servidor_url' => 'https://servidor.teste',
            'modo_igreja.aparelho_token' => 'codigo-de-teste',
        ]);
        Http::fake();
        \Illuminate\Support\Facades\DB::table('sincronizacao_estado')->insert(['chave' => 'ultimo_recebido', 'valor' => '0']);

        $trava = Cache::lock('sincronizacao:executar', 60);
        $this->assertTrue($trava->get());

        try {
            $this->artisan('sincronizacao:executar')
                ->expectsOutputToContain('já está rodando')
                ->assertSuccessful();

            Http::assertNothingSent();
        } finally {
            $trava->release();
        }
    }
}
