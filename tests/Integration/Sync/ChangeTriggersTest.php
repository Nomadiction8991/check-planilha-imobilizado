<?php

declare(strict_types=1);

namespace Tests\Integration\Sync;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * Testa os gatilhos que registram cada alteração em sincronizacao_alteracoes.
 *
 * Os gatilhos só existem no MySQL, por isso esta classe roda pela suíte
 * de integração (phpunit.mysql.xml) e não pela suíte padrão (SQLite).
 */
class ChangeTriggersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'mysql') {
            $this->fail('Estes testes precisam de MySQL. Rode com: phpunit -c phpunit.mysql.xml');
        }
    }

    public function test_registra_insercao_com_valores_depois(): void
    {
        $comum = $this->criarComum();

        DB::table('produtos')->insert([
            'codigo' => 'P-' . uniqid(),
            'comum_id' => $comum,
            'bem' => 'Cadeira',
        ]);

        $registros = $this->alteracoes('produtos');

        $this->assertCount(1, $registros);
        $this->assertSame('insert', $registros[0]->operacao);
        $this->assertNull($registros[0]->antes);
        $this->assertSame('Cadeira', json_decode((string) $registros[0]->depois, true)['bem']);
    }

    public function test_registra_alteracao_com_valores_antes_e_depois(): void
    {
        $comum = $this->criarComum();
        $id = DB::table('produtos')->insertGetId([
            'codigo' => 'P-' . uniqid(),
            'comum_id' => $comum,
            'bem' => 'Cadeira',
        ]);
        $this->limparAlteracoes();

        DB::table('produtos')->where('id_produto', $id)->update(['bem' => 'Mesa']);

        $registros = $this->alteracoes('produtos');

        $this->assertCount(1, $registros);
        $this->assertSame('update', $registros[0]->operacao);
        $this->assertSame('Cadeira', json_decode((string) $registros[0]->antes, true)['bem']);
        $this->assertSame('Mesa', json_decode((string) $registros[0]->depois, true)['bem']);
    }

    public function test_registra_exclusao_com_valores_antes(): void
    {
        $comum = $this->criarComum();
        $id = DB::table('produtos')->insertGetId([
            'codigo' => 'P-' . uniqid(),
            'comum_id' => $comum,
            'bem' => 'Armário',
        ]);
        $this->limparAlteracoes();

        DB::table('produtos')->where('id_produto', $id)->delete();

        $registros = $this->alteracoes('produtos');

        $this->assertCount(1, $registros);
        $this->assertSame('delete', $registros[0]->operacao);
        $this->assertSame('Armário', json_decode((string) $registros[0]->antes, true)['bem']);
        $this->assertNull($registros[0]->depois);
    }

    public function test_nao_registra_quando_a_mudanca_vem_de_fora(): void
    {
        $comum = $this->criarComum();
        $id = DB::table('produtos')->insertGetId([
            'codigo' => 'P-' . uniqid(),
            'comum_id' => $comum,
            'bem' => 'Mesa',
        ]);
        $this->limparAlteracoes();

        // Simula a aplicação de uma mudança que chegou do outro lado da sincronização.
        DB::statement('SET @sincronizacao_aplicando = 1');
        DB::table('produtos')->where('id_produto', $id)->update(['bem' => 'Mesa nova']);
        DB::statement('SET @sincronizacao_aplicando = NULL');

        $this->assertCount(0, $this->alteracoes('produtos'));
    }

    public function test_nao_registra_quando_a_transacao_e_desfeita(): void
    {
        $comum = $this->criarComum();
        $id = DB::table('produtos')->insertGetId([
            'codigo' => 'P-' . uniqid(),
            'comum_id' => $comum,
            'bem' => 'Armário',
        ]);
        $this->limparAlteracoes();

        try {
            DB::transaction(function () use ($id): void {
                DB::table('produtos')->where('id_produto', $id)->update(['bem' => 'Desfeito']);

                throw new RuntimeException('falha proposital para desfazer a transação');
            });
        } catch (RuntimeException) {
            // Esperado: a transação foi desfeita de propósito.
        }

        $this->assertCount(0, $this->alteracoes('produtos'));
    }

    public function test_registra_tabela_com_chave_composta_com_chave_em_json(): void
    {
        $comum = $this->criarComum();
        $produto = DB::table('produtos')->insertGetId([
            'codigo' => 'P-' . uniqid(),
            'comum_id' => $comum,
            'bem' => 'Cadeira',
        ]);
        $this->limparAlteracoes();

        DB::table('import_job_processed')->insert([
            'job_id' => 'job-' . uniqid(),
            'id_produto' => $produto,
            'comum_id' => $comum,
        ]);

        $registros = $this->alteracoes('import_job_processed');

        $this->assertCount(1, $registros);
        $chave = json_decode($registros[0]->chave, true);
        $this->assertSame($produto, $chave['id_produto']);
        $this->assertArrayHasKey('job_id', $chave);
    }

    public function test_registra_tabela_sem_chave_primaria_como_registro_unico(): void
    {
        $this->limparAlteracoes();

        DB::table('configuracoes')->insert(['mail_host' => 'smtp.teste.local']);

        $registros = $this->alteracoes('configuracoes');

        $this->assertCount(1, $registros);
        $this->assertSame('*', $registros[0]->chave);
        $this->assertSame('smtp.teste.local', json_decode((string) $registros[0]->depois, true)['mail_host']);
    }

    public function test_apagar_igreja_registra_dependencias_apagadas_em_cascata(): void
    {
        $comum = $this->criarComum();
        $dependencia = DB::table('dependencias')->insertGetId([
            'descricao' => 'Templo',
            'comum_id' => $comum,
        ]);
        $this->limparAlteracoes();

        DB::table('comums')->where('id', $comum)->delete();

        $registros = $this->alteracoes('dependencias');

        $this->assertCount(1, $registros);
        $this->assertSame('delete', $registros[0]->operacao);
        $this->assertSame((string) $dependencia, $registros[0]->chave);
        $this->assertSame('Templo', json_decode((string) $registros[0]->antes, true)['descricao']);
    }

    public function test_apagar_igreja_registra_usuario_que_perdeu_a_igreja(): void
    {
        $comum = $this->criarComum();
        $usuario = $this->criarUsuario($comum);
        $this->limparAlteracoes();

        DB::table('comums')->where('id', $comum)->delete();

        $registros = $this->alteracoes('usuarios');

        $this->assertCount(1, $registros);
        $this->assertSame('update', $registros[0]->operacao);
        $this->assertSame((string) $usuario, $registros[0]->chave);
        $this->assertSame($comum, json_decode((string) $registros[0]->antes, true)['comum_id']);
        $this->assertNull(json_decode((string) $registros[0]->depois, true)['comum_id']);
    }

    public function test_apagar_importacao_registra_acoes_e_erros_apagados_em_cascata(): void
    {
        $comum = $this->criarComum();
        $usuario = $this->criarUsuario($comum);
        $importacao = DB::table('importacoes')->insertGetId([
            'usuario_id' => $usuario,
            'comum_id' => $comum,
            'arquivo_nome' => 'planilha.csv',
            'arquivo_caminho' => 'importacao/planilha.csv',
        ]);
        DB::table('importacao_acoes')->insert([
            'importacao_id' => $importacao,
            'chave' => 'P-1',
            'tipo' => 'produto',
            'acao' => 'importar',
        ]);
        DB::table('import_erros')->insert([
            'importacao_id' => $importacao,
            'mensagem_erro' => 'linha 2 inválida',
        ]);
        $this->limparAlteracoes();

        DB::table('importacoes')->where('id', $importacao)->delete();

        $acoes = $this->alteracoes('importacao_acoes');
        $erros = $this->alteracoes('import_erros');

        $this->assertCount(1, $acoes);
        $this->assertSame('delete', $acoes[0]->operacao);
        $this->assertCount(1, $erros);
        $this->assertSame('delete', $erros[0]->operacao);
    }

    public function test_apagar_igreja_registra_importacoes_e_seus_erros_em_cascata_de_dois_niveis(): void
    {
        $comum = $this->criarComum();
        $usuario = $this->criarUsuario($comum);
        $importacao = DB::table('importacoes')->insertGetId([
            'usuario_id' => $usuario,
            'comum_id' => $comum,
            'arquivo_nome' => 'planilha.csv',
            'arquivo_caminho' => 'importacao/planilha.csv',
        ]);
        DB::table('import_erros')->insert([
            'importacao_id' => $importacao,
            'mensagem_erro' => 'linha 3 inválida',
        ]);
        $this->limparAlteracoes();

        DB::table('comums')->where('id', $comum)->delete();

        $this->assertCount(1, $this->alteracoes('importacoes'));
        $this->assertCount(1, $this->alteracoes('import_erros'));
        $this->assertSame('delete', $this->alteracoes('import_erros')[0]->operacao);
    }

    public function test_cascata_com_supressao_nao_registra_nada(): void
    {
        $comum = $this->criarComum();
        DB::table('dependencias')->insert(['descricao' => 'Sala', 'comum_id' => $comum]);
        $this->limparAlteracoes();

        // Mudança vinda de fora: a cascata acontece, mas não deve voltar como alteração nova.
        DB::statement('SET @sincronizacao_aplicando = 1');
        DB::table('comums')->where('id', $comum)->delete();
        DB::statement('SET @sincronizacao_aplicando = NULL');

        $this->assertSame(0, DB::table('dependencias')->where('comum_id', $comum)->count());
        $this->assertCount(0, $this->alteracoes('dependencias'));
    }

    public function test_todas_as_tabelas_configuradas_tem_gatilhos(): void
    {
        /** @var list<string> $tabelas */
        $tabelas = config('sincronizacao.tabelas');

        foreach ($tabelas as $tabela) {
            $total = (int) DB::table('information_schema.TRIGGERS')
                ->where('TRIGGER_SCHEMA', DB::raw('DATABASE()'))
                ->where('EVENT_OBJECT_TABLE', $tabela)
                ->count();

            $this->assertGreaterThanOrEqual(3, $total, "A tabela {$tabela} não tem gatilhos suficientes.");
        }
    }

    public function test_cada_registro_recebe_um_uuid_unico(): void
    {
        $comum = $this->criarComum();
        DB::table('produtos')->insert(['codigo' => 'P-' . uniqid(), 'comum_id' => $comum, 'bem' => 'Mesa']);
        DB::table('produtos')->insert(['codigo' => 'P-' . uniqid(), 'comum_id' => $comum, 'bem' => 'Cadeira']);

        $registros = $this->alteracoes('produtos');

        $this->assertCount(2, $registros);
        $this->assertNotEmpty($registros[0]->uuid);
        $this->assertNotSame($registros[0]->uuid, $registros[1]->uuid);
    }

    public function test_origem_vem_da_variavel_da_conexao(): void
    {
        $comum = $this->criarComum();

        DB::statement("SET @sincronizacao_origem = 'aparelho:7'");
        DB::table('produtos')->insert(['codigo' => 'P-' . uniqid(), 'comum_id' => $comum, 'bem' => 'Mesa']);
        DB::statement('SET @sincronizacao_origem = NULL');
        DB::table('produtos')->insert(['codigo' => 'P-' . uniqid(), 'comum_id' => $comum, 'bem' => 'Cadeira']);

        $registros = $this->alteracoes('produtos');

        $this->assertSame('aparelho:7', $registros[0]->origem);
        $this->assertSame('local', $registros[1]->origem);
    }

    private function criarUsuario(int $comum): int
    {
        return (int) DB::table('usuarios')->insertGetId([
            'nome' => 'Usuário de teste',
            'email' => 'usuario-' . uniqid() . '@teste.local',
            'senha' => 'nao-usada',
            'comum_id' => $comum,
        ]);
    }

    private function criarComum(): int
    {
        return (int) DB::table('comums')->insertGetId(['codigo' => 'C-' . uniqid()]);
    }

    private function limparAlteracoes(): void
    {
        DB::table('sincronizacao_alteracoes')->delete();
    }

    /**
     * @return array<int, object>
     */
    private function alteracoes(string $tabela): array
    {
        return DB::table('sincronizacao_alteracoes')
            ->where('tabela', $tabela)
            ->orderBy('id')
            ->get()
            ->all();
    }
}
