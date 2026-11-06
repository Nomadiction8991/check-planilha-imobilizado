<?php

declare(strict_types=1);

namespace Tests\Integration\Sync;

use App\Sync\CopyExporter;
use App\Sync\CopyImporter;
use App\Sync\SyncState;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Cópia inicial: o servidor gera a cópia de todas as tabelas, o notebook importa.
 *
 * A senha do e-mail é criptografada com a chave de cada instalação, então ela viaja
 * em texto (dentro do HTTPS) e o notebook a guarda de novo com a própria chave.
 */
class CopyTest extends TestCase
{
    private const BANCOS = [
        'notebook_a' => 'cp_nb_a',
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

    public function test_copia_leva_os_dados_e_a_senha_do_email_volta_utilizavel_no_notebook(): void
    {
        DB::usingConnection('servidor', function (): void {
            $comum = DB::table('comums')->insertGetId(['codigo' => 'C-9']);
            $dependencia = DB::table('dependencias')->insertGetId(['descricao' => 'Templo', 'comum_id' => $comum]);
            $tipo = DB::table('tipos_bens')->insertGetId(['codigo' => 4, 'descricao' => 'Cadeira']);
            DB::table('produtos')->insert([
                'codigo' => 'P-9',
                'comum_id' => $comum,
                'bem' => 'Mesa',
                'dependencia_id' => $dependencia,
                'tipo_bem_id' => $tipo,
            ]);
            DB::table('configuracoes')->insert([
                'mail_host' => 'smtp.teste.local',
                'mail_password' => Crypt::encryptString('segredo-do-email'),
            ]);
        });

        $linhas = DB::usingConnection('servidor', fn (): array => iterator_to_array(app(CopyExporter::class)->linhas()));

        DB::usingConnection('notebook_a', fn () => app(CopyImporter::class)->importar($linhas));

        foreach (['comums', 'dependencias', 'tipos_bens', 'produtos'] as $tabela) {
            $this->assertSame(
                DB::connection('servidor')->table($tabela)->count(),
                DB::connection('notebook_a')->table($tabela)->count(),
                "A tabela {$tabela} deveria ter as mesmas linhas no notebook.",
            );
        }

        $senha = DB::connection('notebook_a')->table('configuracoes')->value('mail_password');
        $this->assertSame('segredo-do-email', Crypt::decryptString((string) $senha));

        $this->assertSame(
            0,
            DB::connection('notebook_a')->table('sincronizacao_alteracoes')->count(),
            'A cópia inicial não pode gerar registros de alteração no notebook.',
        );

        $cursorDoServidor = (int) DB::connection('servidor')->table('sincronizacao_alteracoes')->max('id');
        $this->assertSame(
            $cursorDoServidor,
            DB::connection('notebook_a')->table('sincronizacao_estado')
                ->where('chave', SyncState::ULTIMO_RECEBIDO)
                ->value('valor') + 0,
        );
    }
}
