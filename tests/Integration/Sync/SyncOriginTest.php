<?php

declare(strict_types=1);

namespace Tests\Integration\Sync;

use App\Sync\DeviceRegistry;
use App\Sync\SyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Com os gatilhos reais do MySQL: a mudança que chega de um notebook é registrada
 * com a origem dele, e o recebimento não devolve essa mudança ao próprio notebook.
 */
class SyncOriginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'mysql') {
            $this->fail('Estes testes precisam de MySQL. Rode com: phpunit -c phpunit.mysql.xml');
        }
    }

    public function test_mudanca_do_aparelho_fica_com_a_origem_dele_e_nao_volta_a_ele(): void
    {
        $aparelho = app(DeviceRegistry::class)->registrar('Notebook da igreja', 7)['aparelho'];
        $comum = DB::table('comums')->insertGetId(['codigo' => 'C-' . uniqid()]);

        app(SyncService::class)->enviar($aparelho, [[
            'uuid' => (string) Str::uuid(),
            'tabela' => 'produtos',
            'chave' => '1',
            'operacao' => 'insert',
            'antes' => null,
            'depois' => ['id_produto' => 1, 'codigo' => 'P-' . uniqid(), 'comum_id' => $comum, 'bem' => 'Mesa'],
            'criado_em' => '2026-11-05 10:00:00',
        ]]);

        $registro = DB::table('sincronizacao_alteracoes')->where('tabela', 'produtos')->first();
        $this->assertNotNull($registro);
        $this->assertSame('aparelho:' . $aparelho->id, $registro->origem);

        $recebidas = app(SyncService::class)->receber($aparelho, 0)['alteracoes'];
        $produtosRecebidos = array_filter($recebidas, fn (array $linha): bool => $linha['tabela'] === 'produtos');

        $this->assertSame([], array_values($produtosRecebidos));
    }

    public function test_mudanca_feita_no_servidor_chega_ao_aparelho(): void
    {
        $aparelho = app(DeviceRegistry::class)->registrar('Notebook da igreja', 7)['aparelho'];
        $comum = DB::table('comums')->insertGetId(['codigo' => 'C-' . uniqid()]);

        DB::table('produtos')->insert(['codigo' => 'P-' . uniqid(), 'comum_id' => $comum, 'bem' => 'Sofá']);

        $recebidas = app(SyncService::class)->receber($aparelho, 0)['alteracoes'];
        $produtosRecebidos = array_values(array_filter($recebidas, fn (array $linha): bool => $linha['tabela'] === 'produtos'));

        $this->assertCount(1, $produtosRecebidos);
        $this->assertSame('local', $produtosRecebidos[0]['origem']);
        $this->assertSame('Sofá', $produtosRecebidos[0]['depois']['bem']);
    }
}
