<?php

declare(strict_types=1);

namespace Tests\Feature\Sync;

use App\Sync\DeviceRegistry;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Envio das alterações do notebook para o servidor: aplica, detecta conflito,
 * ignora o que já chegou antes e recusa tabela que não sincroniza.
 */
class SyncPushTest extends SincronizacaoTestCase
{
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('produtos', function (Blueprint $table): void {
            $table->increments('id_produto');
            $table->string('codigo')->unique();
            $table->integer('comum_id')->nullable();
            $table->string('bem')->nullable();
            $table->boolean('checado')->default(false);
        });

        Schema::create('usuarios', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('nome');
            $table->string('email')->unique();
            $table->string('senha');
            $table->integer('comum_id')->nullable();
            $table->boolean('ativo')->default(true);
        });

        $this->token = app(DeviceRegistry::class)->registrar('Notebook da igreja', 7)['token'];
    }

    public function test_envio_aplica_insercao_no_servidor(): void
    {
        $resposta = $this->enviar([
            $this->alteracao('11111111-1111-4111-8111-111111111111', 'produtos', '5', 'insert', null, [
                'id_produto' => 5,
                'codigo' => 'P-5',
                'comum_id' => 1,
                'bem' => 'Mesa',
                'checado' => false,
            ]),
        ]);

        $resposta->assertOk()->assertJsonPath('aplicadas', 1);
        $this->assertSame('Mesa', DB::table('produtos')->where('id_produto', 5)->value('bem'));
    }

    public function test_envio_repetido_nao_aplica_de_novo(): void
    {
        $alteracao = $this->alteracao('22222222-2222-4222-8222-222222222222', 'produtos', '6', 'insert', null, [
            'id_produto' => 6,
            'codigo' => 'P-6',
            'comum_id' => 1,
            'bem' => 'Cadeira',
            'checado' => false,
        ]);

        $this->enviar([$alteracao])->assertJsonPath('aplicadas', 1);
        $this->enviar([$alteracao])
            ->assertOk()
            ->assertJsonPath('aplicadas', 0)
            ->assertJsonPath('ja_recebidas', 1);

        $this->assertSame(1, DB::table('produtos')->where('id_produto', 6)->count());
    }

    public function test_alteracao_muda_so_os_campos_que_o_notebook_mudou(): void
    {
        DB::table('produtos')->insert(['id_produto' => 7, 'codigo' => 'P-7', 'comum_id' => 1, 'bem' => 'Cadeira', 'checado' => 1]);

        $this->enviar([
            $this->alteracao('33333333-3333-4333-8333-333333333333', 'produtos', '7', 'update',
                ['id_produto' => 7, 'codigo' => 'P-7', 'comum_id' => 1, 'bem' => 'Cadeira', 'checado' => 1],
                ['id_produto' => 7, 'codigo' => 'P-7', 'comum_id' => 1, 'bem' => 'Sofá', 'checado' => 1],
            ),
        ])->assertJsonPath('aplicadas', 1);

        $linha = DB::table('produtos')->where('id_produto', 7)->first();
        $this->assertSame('Sofá', $linha->bem);
        $this->assertSame(1, (int) $linha->checado);
    }

    public function test_conflito_quando_o_servidor_mudou_o_mesmo_campo(): void
    {
        DB::table('produtos')->insert(['id_produto' => 8, 'codigo' => 'P-8', 'comum_id' => 1, 'bem' => 'Armário', 'checado' => 0]);

        $this->enviar([
            $this->alteracao('44444444-4444-4444-8444-444444444444', 'produtos', '8', 'update',
                ['id_produto' => 8, 'codigo' => 'P-8', 'comum_id' => 1, 'bem' => 'Cadeira', 'checado' => 0],
                ['id_produto' => 8, 'codigo' => 'P-8', 'comum_id' => 1, 'bem' => 'Sofá', 'checado' => 0],
            ),
        ])
            ->assertJsonPath('conflitos.0.uuid', '44444444-4444-4444-8444-444444444444')
            ->assertJsonPath('aplicadas', 0);

        $this->assertSame('Armário', DB::table('produtos')->where('id_produto', 8)->value('bem'));
        $this->assertSame(1, DB::table('sincronizacao_conflitos')->where('status', 'pendente')->count());
    }

    public function test_usuario_segue_o_servidor_quando_ha_conflito(): void
    {
        DB::table('usuarios')->insert(['id' => 9, 'nome' => 'Nome novo', 'email' => 'a@teste.local', 'senha' => 'x', 'comum_id' => 1, 'ativo' => 1]);

        $this->enviar([
            $this->alteracao('55555555-5555-4555-8555-555555555555', 'usuarios', '9', 'update',
                ['id' => 9, 'nome' => 'Nome antigo', 'email' => 'a@teste.local', 'senha' => 'x', 'comum_id' => 1, 'ativo' => 1],
                ['id' => 9, 'nome' => 'Nome do notebook', 'email' => 'a@teste.local', 'senha' => 'x', 'comum_id' => 1, 'ativo' => 1],
            ),
        ])
            ->assertJsonPath('conflitos', [])
            ->assertJsonPath('ignoradas', 1);

        $this->assertSame('Nome novo', DB::table('usuarios')->where('id', 9)->value('nome'));
    }

    public function test_exclusao_de_registro_que_o_servidor_ja_apagou_e_ignorada(): void
    {
        $this->enviar([
            $this->alteracao('66666666-6666-4666-8666-666666666666', 'produtos', '99', 'delete',
                ['id_produto' => 99, 'codigo' => 'P-99', 'comum_id' => 1, 'bem' => 'Mesa', 'checado' => 0],
                null,
            ),
        ])->assertJsonPath('ignoradas', 1);
    }

    public function test_tabela_que_nao_sincroniza_e_recusada(): void
    {
        $this->enviar([
            $this->alteracao('77777777-7777-4777-8777-777777777777', 'sessions', 'abc', 'insert', null, ['id' => 'abc']),
        ])
            ->assertOk()
            ->assertJsonPath('aplicadas', 0)
            ->assertJsonPath('erros.0.uuid', '77777777-7777-4777-8777-777777777777');
    }

    public function test_envio_sem_alteracoes_responde_422(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/sincronizacao/enviar', [])
            ->assertUnprocessable();
    }

    /**
     * @param  array<string, mixed>|null  $antes
     * @param  array<string, mixed>|null  $depois
     * @return array<string, mixed>
     */
    private function alteracao(string $uuid, string $tabela, string $chave, string $operacao, ?array $antes, ?array $depois): array
    {
        return [
            'uuid' => $uuid,
            'tabela' => $tabela,
            'chave' => $chave,
            'operacao' => $operacao,
            'antes' => $antes,
            'depois' => $depois,
            'criado_em' => '2026-11-05 10:00:00',
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $alteracoes
     */
    private function enviar(array $alteracoes): \Illuminate\Testing\TestResponse
    {
        return $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/sincronizacao/enviar', ['alteracoes' => $alteracoes]);
    }
}
