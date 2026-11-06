<?php

declare(strict_types=1);

namespace Tests\Feature\Sync;

use App\Sync\DeviceRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Recebimento: o notebook pede as alterações feitas em outros lugares,
 * e nunca recebe de volta as próprias.
 */
class SyncPullTest extends SincronizacaoTestCase
{
    private string $token;

    private int $aparelhoId;

    protected function setUp(): void
    {
        parent::setUp();

        $registro = app(DeviceRegistry::class)->registrar('Notebook da igreja', 7);
        $this->token = $registro['token'];
        $this->aparelhoId = (int) $registro['aparelho']->id;
    }

    public function test_recebimento_devolve_alteracoes_de_outras_origens_e_nao_as_proprias(): void
    {
        $this->registrar('local', 'produtos', '1');
        $this->registrar('aparelho:' . $this->aparelhoId, 'produtos', '2');
        $this->registrar('aparelho:99', 'produtos', '3');

        $resposta = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/sincronizacao/receber?depois=0');

        $resposta->assertOk();
        $origens = array_column($resposta->json('alteracoes'), 'origem');

        $this->assertSame(['local', 'aparelho:99'], $origens);
    }

    public function test_recebimento_informa_o_cursor_para_a_proxima_chamada(): void
    {
        $primeira = $this->registrar('local', 'produtos', '1');
        $segunda = $this->registrar('local', 'produtos', '2');

        $resposta = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/sincronizacao/receber?depois=0');

        $this->assertSame($segunda, $resposta->json('cursor'));
        $this->assertFalse($resposta->json('mais'));
        $this->assertLessThan($segunda, $primeira);
    }

    public function test_recebimento_pede_um_cursor_valido(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/sincronizacao/receber?depois=abc')
            ->assertUnprocessable();
    }

    private function registrar(string $origem, string $tabela, string $chave): int
    {
        return (int) DB::table('sincronizacao_alteracoes')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'tabela' => $tabela,
            'chave' => $chave,
            'operacao' => 'update',
            'antes' => json_encode(['bem' => 'Cadeira']),
            'depois' => json_encode(['bem' => 'Mesa']),
            'origem' => $origem,
            'criado_em' => now(),
        ]);
    }
}
