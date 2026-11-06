<?php

declare(strict_types=1);

namespace Tests\Integration\Sync;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * O comando que baixa a cópia do servidor. Roda no MySQL, porque grava nas tabelas reais.
 */
class CopyCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'mysql') {
            $this->fail('Estes testes precisam de MySQL. Rode com: phpunit -c phpunit.mysql.xml');
        }

        config([
            'modo_igreja.servidor_url' => 'https://servidor.teste',
            'modo_igreja.aparelho_token' => 'codigo-de-teste',
        ]);

        // Cada teste começa limpo. Primeiro as tabelas de dados (a exclusão gera registros de alteração),
        // depois o registro e o cursor, para o próximo teste não herdar nada.
        DB::table('comums')->delete();
        DB::table('sincronizacao_alteracoes')->delete();
        DB::table('sincronizacao_estado')->delete();
    }

    /**
     * Sem transação de teste: a preparação da numeração faz uma alteração de estrutura, que
     * confirma transações abertas. Assim o teste roda do mesmo jeito que em produção.
     */
    public function beginDatabaseTransaction(): void
    {
    }

    public function test_baixa_a_copia_do_servidor_e_grava_neste_computador(): void
    {
        Http::fake($this->respostasDoServidor($this->copiaCompleta()));

        $this->artisan('sincronizacao:copia')
            ->expectsOutputToContain('Cópia baixada')
            ->assertSuccessful();

        $this->assertSame('C-NOVO', DB::table('comums')->value('codigo'));
        Http::assertSent(fn ($pedido): bool => $pedido->hasHeader('Authorization', 'Bearer codigo-de-teste'));
    }

    public function test_numeracao_do_notebook_comeca_na_faixa_do_aparelho(): void
    {
        Http::fake($this->respostasDoServidor($this->copiaCompleta(), 300000000));

        $this->artisan('sincronizacao:copia')->assertSuccessful();

        // Itens criados depois da cópia começam em 300 milhões, longe dos números do servidor e de outros notebooks.
        $proximo = (int) DB::selectOne("SHOW TABLE STATUS LIKE 'comums'")->Auto_increment;
        $this->assertSame(300000000, $proximo);
    }

    public function test_recusa_baixar_de_novo_quando_ja_existe_copia(): void
    {
        DB::table('sincronizacao_estado')->insert(['chave' => 'ultimo_recebido', 'valor' => '0']);
        Http::fake($this->respostasDoServidor($this->copiaCompleta()));

        $this->artisan('sincronizacao:copia')
            ->expectsOutputToContain('já tem a cópia')
            ->assertFailed();

        Http::assertNothingSent();
    }

    public function test_recusa_baixar_quando_ha_alteracoes_locais_nao_enviadas(): void
    {
        DB::table('sincronizacao_alteracoes')->insert([
            'uuid' => '99999999-9999-4999-8999-999999999999',
            'tabela' => 'comums',
            'chave' => '5',
            'operacao' => 'insert',
            'origem' => 'local',
            'criado_em' => now(),
        ]);
        Http::fake($this->respostasDoServidor($this->copiaCompleta()));

        $this->artisan('sincronizacao:copia')
            ->expectsOutputToContain('ainda não foram enviadas')
            ->assertFailed();

        Http::assertNothingSent();
    }

    public function test_copia_incompleta_nao_grava_nada(): void
    {
        // Dado que já veio do servidor numa cópia anterior: entra sem registro de alteração local.
        DB::statement('SET @sincronizacao_aplicando = 1');
        DB::table('comums')->insert(['codigo' => 'C-LOCAL']);
        DB::statement('SET @sincronizacao_aplicando = NULL');

        $linhas = $this->linhasDaCopia();
        $cortada = implode("\n", array_slice($linhas, 0, -1)) . "\n";
        Http::fake($this->respostasDoServidor($cortada));

        $this->artisan('sincronizacao:copia')
            ->expectsOutputToContain('incompleta')
            ->assertFailed();

        $this->assertSame('C-LOCAL', DB::table('comums')->value('codigo'));
        $this->assertFalse(DB::table('sincronizacao_estado')->where('chave', 'ultimo_recebido')->exists());
    }

    /**
     * @return array<string, \Illuminate\Http\Client\ResponseSequence|\GuzzleHttp\Promise\PromiseInterface|array>
     */
    private function respostasDoServidor(string $copia, int $faixa = 100000000): array
    {
        return [
            '*/api/sincronizacao/faixa' => Http::response(['faixa_inicial' => $faixa]),
            '*/api/sincronizacao/copia' => Http::response($copia),
        ];
    }

    private function copiaCompleta(): string
    {
        return implode("\n", $this->linhasDaCopia()) . "\n";
    }

    /**
     * @return list<string>
     */
    private function linhasDaCopia(): array
    {
        return [
            json_encode(['tipo' => 'cabecalho', 'versao' => 1, 'cursor' => 0], JSON_THROW_ON_ERROR),
            json_encode([
                'tipo' => 'linha',
                'tabela' => 'comums',
                'dados' => [
                    'id' => 1,
                    'codigo' => 'C-NOVO',
                    'descricao' => 'Igreja nova',
                ],
            ], JSON_THROW_ON_ERROR),
            json_encode(['tipo' => 'fim', 'linhas' => 1], JSON_THROW_ON_ERROR),
        ];
    }
}
