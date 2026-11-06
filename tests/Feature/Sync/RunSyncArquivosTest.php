<?php

declare(strict_types=1);

namespace Tests\Feature\Sync;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * O comando de sincronização também leva as planilhas e a auditoria, com a soma SHA-256 no envio.
 */
class RunSyncArquivosTest extends SincronizacaoTestCase
{
    private string $pasta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pasta = sys_get_temp_dir() . '/cp-run-arquivos-' . uniqid();
        mkdir($this->pasta . '/planilhas', 0775, true);
        file_put_contents($this->pasta . '/planilhas/local.csv', 'do notebook');
        \Illuminate\Support\Facades\DB::table('sincronizacao_estado')->insert(['chave' => 'ultimo_recebido', 'valor' => '0']);

        config([
            'modo_igreja.servidor_url' => 'https://servidor.teste',
            'modo_igreja.aparelho_token' => 'codigo-de-teste',
            'sincronizacao.pasta_planilhas' => $this->pasta . '/planilhas',
            'sincronizacao.arquivo_auditoria' => $this->pasta . '/audit-log.jsonl',
        ]);
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->pasta));

        parent::tearDown();
    }

    public function test_envia_a_planilha_que_falta_no_servidor_com_o_sha256(): void
    {
        Http::fake(function (Request $pedido) {
            $url = $pedido->url();

            if (str_ends_with($url, '/api/sincronizacao/receber?depois=0')) {
                return Http::response(['alteracoes' => [], 'cursor' => 0, 'mais' => false]);
            }

            if (str_ends_with($url, '/api/sincronizacao/planilhas') && $pedido->method() === 'GET') {
                return Http::response(['arquivos' => []]);
            }

            if (str_ends_with($url, '/api/sincronizacao/planilhas/local.csv') && $pedido->method() === 'PUT') {
                return Http::response(['status' => 'gravado']);
            }

            if (str_ends_with($url, '/api/sincronizacao/auditoria') && $pedido->method() === 'GET') {
                return Http::response(['linhas' => []]);
            }

            return Http::response(['erro' => 'rota inesperada'], 404);
        });

        $this->artisan('sincronizacao:executar')
            ->expectsOutputToContain('Planilhas: enviadas 1')
            ->assertSuccessful();

        Http::assertSent(fn (Request $pedido): bool => $pedido->method() === 'PUT'
            && str_ends_with($pedido->url(), '/planilhas/local.csv')
            && $pedido->header('X-Sha256')[0] === hash('sha256', 'do notebook'));
    }
}
