<?php

declare(strict_types=1);

namespace Tests\Feature\Sync;

use App\Sync\DeviceRegistry;
use Illuminate\Support\Facades\DB;

class DeviceRegistryTest extends SincronizacaoTestCase
{
    public function test_aparelho_novo_recebe_um_token_que_autentica(): void
    {
        $registro = app(DeviceRegistry::class)->registrar('Notebook da igreja', 7);

        $this->assertNotEmpty($registro['token']);

        $encontrado = app(DeviceRegistry::class)->autenticar($registro['token']);

        $this->assertNotNull($encontrado);
        $this->assertSame($registro['aparelho']->id, $encontrado->id);
    }

    public function test_token_nao_fica_salvo_em_texto_puro(): void
    {
        $registro = app(DeviceRegistry::class)->registrar('Notebook da igreja', 7);

        $hash = DB::table('sincronizacao_aparelhos')->value('token_hash');

        $this->assertNotSame($registro['token'], $hash);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', (string) $hash);
    }

    public function test_token_revogado_deixa_de_autenticar(): void
    {
        $aparelhos = app(DeviceRegistry::class);
        $registro = $aparelhos->registrar('Notebook da igreja', 7);

        $aparelhos->revogar($registro['aparelho']);

        $this->assertNull($aparelhos->autenticar($registro['token']));
    }

    public function test_token_errado_ou_ausente_nao_autentica(): void
    {
        $aparelhos = app(DeviceRegistry::class);
        $aparelhos->registrar('Notebook da igreja', 7);

        $this->assertNull($aparelhos->autenticar('token-inventado'));
        $this->assertNull($aparelhos->autenticar(null));
    }

    public function test_comando_revoga_aparelho_pelo_numero(): void
    {
        $registro = app(DeviceRegistry::class)->registrar('Notebook da igreja', 7);

        $this->artisan('sincronizacao:revogar', ['aparelho' => $registro['aparelho']->id])
            ->expectsOutputToContain('revogado')
            ->assertSuccessful();

        $this->assertNull(app(DeviceRegistry::class)->autenticar($registro['token']));
    }

    public function test_comando_cadastra_aparelho_e_mostra_o_codigo_uma_vez(): void
    {
        $this->artisan('sincronizacao:aparelho', ['nome' => 'Notebook da igreja', 'usuario' => 7])
            ->expectsOutputToContain('guarde-o')
            ->assertSuccessful();

        $this->assertSame(1, DB::table('sincronizacao_aparelhos')->count());
    }
}
