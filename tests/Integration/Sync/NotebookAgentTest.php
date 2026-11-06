<?php

declare(strict_types=1);

namespace Tests\Integration\Sync;

use App\Sync\Device;
use App\Sync\DeviceRegistry;
use App\Sync\NotebookAgent;
use App\Sync\SyncState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\LocalSyncTransport;
use Tests\TestCase;

/**
 * Ponta a ponta com três bancos MySQL no mesmo servidor: dois notebooks e o servidor.
 *
 * Cada banco tem o schema completo e os gatilhos reais. O transporte chama o servidor
 * no mesmo processo, então o teste cobre o caminho inteiro sem rede.
 */
class NotebookAgentTest extends TestCase
{
    private const BANCOS = [
        'notebook_a' => 'cp_nb_a',
        'notebook_b' => 'cp_nb_b',
        'servidor' => 'cp_servidor',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'mysql') {
            $this->fail('Estes testes precisam de MySQL. Rode com: phpunit -c phpunit.mysql.xml');
        }

        foreach (self::BANCOS as $conexao => $banco) {
            DB::connection('mysql')->statement(
                "CREATE DATABASE IF NOT EXISTS `{$banco}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
            );

            config(["database.connections.{$conexao}" => array_merge(
                config('database.connections.mysql'),
                ['database' => $banco],
            )]);
            DB::purge($conexao);

            $this->artisan('migrate:fresh', ['--database' => $conexao, '--force' => true])->assertSuccessful();
        }
    }

    public function test_mudanca_do_notebook_chega_ao_servidor_e_ao_outro_notebook_sem_eco(): void
    {
        $dispositivoA = $this->cadastrarNoServidor('Notebook A');
        $dispositivoB = $this->cadastrarNoServidor('Notebook B');

        $this->noNotebook('notebook_a', function (): void {
            $comum = DB::table('comums')->insertGetId(['codigo' => 'C-1']);
            DB::table('produtos')->insert(['codigo' => 'P-1', 'comum_id' => $comum, 'bem' => 'Mesa']);
        });

        $this->sincronizar('notebook_a', $dispositivoA);

        $this->assertSame(1, DB::connection('servidor')->table('produtos')->count());

        $this->sincronizar('notebook_b', $dispositivoB);

        $this->assertSame('Mesa', DB::connection('notebook_b')->table('produtos')->value('bem'));
        $this->assertSame(
            0,
            DB::connection('notebook_b')->table('sincronizacao_alteracoes')->where('tabela', 'produtos')->count(),
            'A mudança recebida não pode voltar como alteração do notebook B.',
        );
    }

    public function test_notebook_nao_recebe_de_volta_a_propria_mudanca(): void
    {
        $dispositivoA = $this->cadastrarNoServidor('Notebook A');

        $this->noNotebook('notebook_a', fn () => DB::table('comums')->insert(['codigo' => 'C-2']));

        $this->sincronizar('notebook_a', $dispositivoA);
        $registrosAntes = DB::connection('notebook_a')->table('sincronizacao_alteracoes')->count();

        $resumo = $this->sincronizar('notebook_a', $dispositivoA);

        $this->assertSame(0, $resumo['enviadas']);
        $this->assertSame(0, $resumo['recebidas']);
        $this->assertSame(
            $registrosAntes,
            DB::connection('notebook_a')->table('sincronizacao_alteracoes')->count(),
        );
    }

    public function test_mudanca_feita_no_servidor_chega_ao_notebook_sem_virar_alteracao_local(): void
    {
        $dispositivoA = $this->cadastrarNoServidor('Notebook A');

        $this->noNotebook('notebook_a', function (): void {
            $comum = DB::table('comums')->insertGetId(['codigo' => 'C-3']);
            DB::table('produtos')->insert(['codigo' => 'P-3', 'comum_id' => $comum, 'bem' => 'Cadeira']);
        });
        $this->sincronizar('notebook_a', $dispositivoA);

        // Alteração feita direto no servidor (no escritório).
        DB::connection('servidor')->table('produtos')->where('codigo', 'P-3')->update(['bem' => 'Sofá']);

        $resumo = $this->sincronizar('notebook_a', $dispositivoA);

        $this->assertSame('Sofá', DB::connection('notebook_a')->table('produtos')->where('codigo', 'P-3')->value('bem'));
        $this->assertGreaterThan(0, $resumo['recebidas']);
        $this->assertSame(
            1,
            DB::connection('notebook_a')->table('sincronizacao_alteracoes')->where('tabela', 'produtos')->count(),
            'Só a inserção original do notebook pode estar no registro local.',
        );
    }

    public function test_alteracao_recusada_pelo_servidor_nao_avanca_o_cursor_de_envio(): void
    {
        $dispositivoA = $this->cadastrarNoServidor('Notebook A');

        // Tabela que não sincroniza: o servidor sempre recusa esta alteração.
        $this->noNotebook('notebook_a', fn () => DB::table('sincronizacao_alteracoes')->insert([
            'uuid' => (string) Str::uuid(),
            'tabela' => 'sessions',
            'chave' => 'abc',
            'operacao' => 'insert',
            'antes' => null,
            'depois' => json_encode(['id' => 'abc']),
            'origem' => 'local',
            'criado_em' => now(),
        ]));

        $primeiro = $this->sincronizar('notebook_a', $dispositivoA);
        $this->assertCount(1, $primeiro['erros']);
        $this->assertSame(0, (int) DB::connection('notebook_a')->table('sincronizacao_estado')
            ->where('chave', SyncState::ULTIMO_ENVIADO)
            ->value('valor'));

        $segundo = $this->sincronizar('notebook_a', $dispositivoA);
        $this->assertCount(1, $segundo['erros'], 'A alteração recusada precisa ser enviada de novo na próxima vez.');
    }

    private function cadastrarNoServidor(string $nome): Device
    {
        return DB::usingConnection('servidor', function () use ($nome): Device {
            return app(DeviceRegistry::class)->registrar($nome, 7)['aparelho'];
        });
    }

    /**
     * @return array{enviadas: int, recebidas: int}
     */
    private function sincronizar(string $conexao, Device $dispositivo): array
    {
        return DB::usingConnection($conexao, function () use ($dispositivo): array {
            return app(NotebookAgent::class)->executar(new LocalSyncTransport((int) $dispositivo->id));
        });
    }

    private function noNotebook(string $conexao, callable $acao): mixed
    {
        return DB::usingConnection($conexao, $acao);
    }
}
