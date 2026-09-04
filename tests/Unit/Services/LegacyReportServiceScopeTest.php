<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Services\LegacyReportService;
use App\Services\LegacyReportTemplateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

final class LegacyReportServiceScopeTest extends TestCase
{
    private LegacyReportService $service;

    private LegacyAuthSessionServiceInterface&MockInterface $auth;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auth = $this->mock(LegacyAuthSessionServiceInterface::class);
        $this->auth->shouldReceive('currentUser')->zeroOrMoreTimes()->andReturn([
            'id' => 9,
            'nome' => 'Maria Silva',
        ]);
        $this->service = new LegacyReportService(new LegacyReportTemplateService(), $this->auth);
    }

    public function testRestrictScopeToPermittedAdministrationsAndChurches(): void
    {
        $this->createScopeTables();
        $this->insertAdministrationsAndChurches();
        Session::put([
            'is_admin' => false,
            'administracao_id' => 10,
            'administracoes_permitidas' => [20],
        ]);

        self::assertSame([10, 20], $this->service->administrationOptions()->pluck('id')->all());
        self::assertSame([100, 200], $this->service->churchOptions()->pluck('id')->all());
        self::assertSame([], $this->service->churchOptions(30)->pluck('id')->all());
    }

    public function testAdministratorKeepsGlobalReportOptions(): void
    {
        $this->createScopeTables();
        $this->insertAdministrationsAndChurches();
        Session::put([
            'is_admin' => true,
            'administracao_id' => null,
            'administracoes_permitidas' => [],
        ]);

        self::assertSame([10, 20, 30], $this->service->administrationOptions()->pluck('id')->all());
        self::assertSame([100, 200, 300], $this->service->churchOptions()->pluck('id')->all());
    }

    public function testRejectsReportPreviewForChurchOutsideScopeBeforeLoadingReportData(): void
    {
        $this->createScopeTables();
        $this->insertAdministrationsAndChurches();
        Session::put([
            'is_admin' => false,
            'administracao_id' => 10,
            'administracoes_permitidas' => [],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('fora do seu escopo permitido');

        $this->service->buildReportPreview(300, '14.1');
    }

    public function testRejectsChangeHistoryForChurchOutsideScope(): void
    {
        $this->createScopeTables();
        $this->insertAdministrationsAndChurches();
        Session::put([
            'is_admin' => false,
            'administracao_id' => 10,
            'administracoes_permitidas' => [],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('fora do seu escopo permitido');

        $this->service->buildChangeHistory(300, []);
    }

    private function createScopeTables(): void
    {
        DB::statement('CREATE TABLE administracoes (id INTEGER PRIMARY KEY, descricao VARCHAR(255))');
        DB::statement('CREATE TABLE comums (id INTEGER PRIMARY KEY, codigo VARCHAR(50), cnpj VARCHAR(255), descricao VARCHAR(255), administracao_id INTEGER, estado VARCHAR(2), administracao VARCHAR(255), cidade VARCHAR(255), setor VARCHAR(255), estado_administracao VARCHAR(2), cidade_administracao VARCHAR(255))');
    }

    private function insertAdministrationsAndChurches(): void
    {
        DB::table('administracoes')->insert([
            ['id' => 10, 'descricao' => 'Administração A'],
            ['id' => 20, 'descricao' => 'Administração B'],
            ['id' => 30, 'descricao' => 'Administração C'],
        ]);
        DB::table('comums')->insert([
            ['id' => 100, 'codigo' => 'A-001', 'descricao' => 'Igreja A', 'administracao_id' => 10, 'estado' => 'MT'],
            ['id' => 200, 'codigo' => 'B-001', 'descricao' => 'Igreja B', 'administracao_id' => 20, 'estado' => 'SP'],
            ['id' => 300, 'codigo' => 'C-001', 'descricao' => 'Igreja C', 'administracao_id' => 30, 'estado' => 'RJ'],
        ]);
    }
}
