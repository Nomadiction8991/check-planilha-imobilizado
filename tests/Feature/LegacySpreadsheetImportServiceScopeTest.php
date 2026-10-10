<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\LegacySpreadsheetImportService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

/**
 * Escopo administrativo vazio não pode significar acesso global.
 *
 * Um usuário legado não-admin sem administracoes_permitidas e sem
 * administracao_id deve ver zero importações; antes da correção o helper
 * devolvia null e a query não aplicava nenhum filtro, expondo
 * importações de outras administrações.
 */
final class LegacySpreadsheetImportServiceScopeTest extends TestCase
{
    private const ADMIN_A = 1;
    private const ADMIN_B = 2;
    private const CHURCH_A = 100;
    private const CHURCH_B = 200;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTables();
        $this->seedData();
    }

    public function testNonAdminWithoutScopeReceivesNoImports(): void
    {
        Session::put([
            'usuario_id' => 500,
            'is_admin' => false,
            'administracoes_permitidas' => [],
        ]);

        $service = new LegacySpreadsheetImportService();

        self::assertSame([], $service->recentImports(null, 5));
    }

    public function testNonAdminWithoutScopeDoesNotLeakOtherAdministrationImports(): void
    {
        Session::put([
            'usuario_id' => 500,
            'is_admin' => false,
            'administracoes_permitidas' => [],
        ]);

        $service = new LegacySpreadsheetImportService();

        $filenames = array_column($service->recentImports(null, 5), 'arquivo_nome');

        self::assertSame([], $filenames);
    }

    public function testAdminStillSeesImportsFromEveryAdministration(): void
    {
        Session::put([
            'usuario_id' => 1,
            'is_admin' => true,
        ]);

        $service = new LegacySpreadsheetImportService();

        $imports = $service->recentImports(null, 5);

        self::assertCount(2, $imports);
        // recentImports ordena por i.id DESC: id 2 (A-central) antes de id 1 (B-private).
        self::assertSame('A-central.csv', $imports[0]['arquivo_nome']);
        self::assertSame('B-private.csv', $imports[1]['arquivo_nome']);
    }

    public function testNonAdminSeesOnlyImportsOfPermittedAdministrations(): void
    {
        Session::put([
            'usuario_id' => 501,
            'is_admin' => false,
            'administracoes_permitidas' => [self::ADMIN_A],
        ]);

        $service = new LegacySpreadsheetImportService();

        $imports = $service->recentImports(null, 5);

        self::assertCount(1, $imports);
        self::assertSame('A-central.csv', $imports[0]['arquivo_nome']);
        self::assertSame(self::ADMIN_A, $imports[0]['administracao_id']);
    }

    public function testNonAdminWithEmptyPermittedListFallsBackToCurrentAdministration(): void
    {
        Session::put([
            'usuario_id' => 502,
            'is_admin' => false,
            'administracoes_permitidas' => [],
            'administracao_id' => self::ADMIN_B,
        ]);

        $service = new LegacySpreadsheetImportService();

        $imports = $service->recentImports(null, 5);

        self::assertCount(1, $imports);
        self::assertSame('B-private.csv', $imports[0]['arquivo_nome']);
    }

    private function createTables(): void
    {
        DB::statement('CREATE TABLE administracoes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            descricao VARCHAR(255) NOT NULL
        )');

        DB::statement('CREATE TABLE comums (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            codigo VARCHAR(50) NOT NULL,
            descricao VARCHAR(255) DEFAULT NULL,
            administracao_id INTEGER DEFAULT NULL
        )');

        DB::statement('CREATE TABLE usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            senha VARCHAR(255) NOT NULL,
            ativo TINYINT DEFAULT 1,
            administracao_id INTEGER DEFAULT NULL,
            comum_id INTEGER DEFAULT NULL,
            tipo VARCHAR(50) DEFAULT NULL
        )');

        DB::statement('CREATE TABLE importacoes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER NOT NULL,
            comum_id INTEGER DEFAULT NULL,
            administracao_id INTEGER DEFAULT NULL,
            arquivo_nome VARCHAR(255) NOT NULL,
            total_linhas INTEGER DEFAULT 0,
            linhas_processadas INTEGER DEFAULT 0,
            linhas_sucesso INTEGER DEFAULT 0,
            linhas_erro INTEGER DEFAULT 0,
            porcentagem DECIMAL(5,2) DEFAULT 0,
            status VARCHAR(50) DEFAULT \'aguardando\',
            iniciada_em TIMESTAMP DEFAULT NULL,
            concluida_em TIMESTAMP DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');
    }

    private function seedData(): void
    {
        DB::table('administracoes')->insert([
            ['id' => self::ADMIN_A, 'descricao' => 'Administração Centro'],
            ['id' => self::ADMIN_B, 'descricao' => 'Administração Zona Leste'],
        ]);

        DB::table('comums')->insert([
            [
                'id' => self::CHURCH_A,
                'codigo' => '09-0001',
                'descricao' => 'Central',
                'administracao_id' => self::ADMIN_A,
            ],
            [
                'id' => self::CHURCH_B,
                'codigo' => '09-0002',
                'descricao' => 'Zona Leste',
                'administracao_id' => self::ADMIN_B,
            ],
        ]);

        DB::table('usuarios')->insert([
            [
                'id' => 1,
                'nome' => 'Administrador',
                'email' => 'ADMIN@LOCALHOST',
                'senha' => 'x',
                'ativo' => 1,
                'administracao_id' => null,
                'comum_id' => null,
                'tipo' => 'administrador',
            ],
            [
                'id' => 10,
                'nome' => 'Maria Silva',
                'email' => 'maria@exemplo.com',
                'senha' => 'x',
                'ativo' => 1,
                'administracao_id' => self::ADMIN_A,
                'comum_id' => self::CHURCH_A,
                'tipo' => null,
            ],
            [
                'id' => 11,
                'nome' => 'João Souza',
                'email' => 'joao@exemplo.com',
                'senha' => 'x',
                'ativo' => 1,
                'administracao_id' => self::ADMIN_B,
                'comum_id' => self::CHURCH_B,
                'tipo' => null,
            ],
        ]);

        DB::table('importacoes')->insert([
            [
                'id' => 1,
                'usuario_id' => 11,
                'comum_id' => self::CHURCH_B,
                'administracao_id' => self::ADMIN_B,
                'arquivo_nome' => 'B-private.csv',
                'total_linhas' => 10,
                'linhas_processadas' => 10,
                'linhas_sucesso' => 10,
                'linhas_erro' => 0,
                'porcentagem' => 100,
                'status' => 'concluida',
                'iniciada_em' => '2026-10-01 08:00:00',
                'concluida_em' => '2026-10-01 08:10:00',
            ],
            [
                'id' => 2,
                'usuario_id' => 10,
                'comum_id' => self::CHURCH_A,
                'administracao_id' => self::ADMIN_A,
                'arquivo_nome' => 'A-central.csv',
                'total_linhas' => 20,
                'linhas_processadas' => 20,
                'linhas_sucesso' => 19,
                'linhas_erro' => 1,
                'porcentagem' => 100,
                'status' => 'concluida',
                'iniciada_em' => '2026-09-30 08:00:00',
                'concluida_em' => '2026-09-30 08:10:00',
            ],
        ]);
    }
}
