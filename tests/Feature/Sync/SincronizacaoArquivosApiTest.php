<?php

declare(strict_types=1);

namespace Tests\Feature\Sync;

use App\Sync\DeviceRegistry;

/**
 * Rotas de planilhas e auditoria do servidor: conferência do SHA-256, repetição e acesso por aparelho.
 */
class SincronizacaoArquivosApiTest extends SincronizacaoTestCase
{
    private string $token;

    private string $pasta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pasta = sys_get_temp_dir() . '/cp-api-arquivos-' . uniqid();
        config([
            'sincronizacao.pasta_planilhas' => $this->pasta . '/planilhas',
            'sincronizacao.arquivo_auditoria' => $this->pasta . '/audit-log.jsonl',
        ]);

        $this->token = app(DeviceRegistry::class)->registrar('Notebook da igreja', 7)['token'];
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->pasta));

        parent::tearDown();
    }

    public function test_envio_com_sha256_errado_responde_422_e_nao_grava(): void
    {
        $this->enviarPlanilha('a.csv', 'conteudo', str_repeat('0', 64))->assertStatus(422);

        $this->assertFileDoesNotExist($this->pasta . '/planilhas/a.csv');
    }

    public function test_envio_repetido_responde_ja_existe(): void
    {
        $sha = hash('sha256', 'conteudo');

        $this->enviarPlanilha('a.csv', 'conteudo', $sha)->assertOk()->assertJsonPath('status', 'gravado');
        $this->enviarPlanilha('a.csv', 'conteudo', $sha)->assertOk()->assertJsonPath('status', 'ja_existe');
    }

    public function test_baixar_planilha_devolve_o_conteudo_e_o_sha256(): void
    {
        $this->enviarPlanilha('b.csv', 'linha 1', hash('sha256', 'linha 1'));

        $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->get('/api/sincronizacao/planilhas/b.csv')
            ->assertOk()
            ->assertHeader('X-Sha256', hash('sha256', 'linha 1'));
    }

    public function test_auditoria_so_responde_para_aparelho_autorizado(): void
    {
        $this->getJson('/api/sincronizacao/auditoria')->assertUnauthorized();
    }

    private function enviarPlanilha(string $nome, string $conteudo, string $sha256): \Illuminate\Testing\TestResponse
    {
        return $this->call(
            'PUT',
            '/api/sincronizacao/planilhas/' . $nome,
            [],
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->token, 'HTTP_X_SHA256' => $sha256, 'CONTENT_TYPE' => 'application/octet-stream'],
            $conteudo,
        );
    }
}
