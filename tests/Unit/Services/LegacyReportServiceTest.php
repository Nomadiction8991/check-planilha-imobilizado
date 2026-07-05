<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Models\Legacy\Comum;
use App\Services\LegacyReportService;
use App\Services\LegacyReportTemplateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

final class LegacyReportServiceTest extends TestCase
{
    private LegacyReportTemplateService $templates;
    private LegacyAuthSessionServiceInterface&MockInterface $auth;
    private LegacyReportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->templates = new LegacyReportTemplateService();
        $this->auth = $this->mock(LegacyAuthSessionServiceInterface::class);

        $this->service = new LegacyReportService($this->templates, $this->auth);
    }

    /**
     * Mock the DB query chain for 'produtos as p' table used by
     * loadProducts141() and loadProducts146() to return no rows.
     */
    private function mockProductsTableReturnsEmpty(): void
    {
        $queryMock = $this->mock(\Illuminate\Database\Query\Builder::class);

        $queryMock->shouldReceive('leftJoin')
            ->zeroOrMoreTimes()
            ->andReturn($queryMock);

        $queryMock->shouldReceive('where')
            ->zeroOrMoreTimes()
            ->andReturn($queryMock);

        $queryMock->shouldReceive('orderBy')
            ->zeroOrMoreTimes()
            ->andReturn($queryMock);

        $queryMock->shouldReceive('get')
            ->zeroOrMoreTimes()
            ->andReturn(collect());

        DB::shouldReceive('table')
            ->with('produtos as p')
            ->zeroOrMoreTimes()
            ->andReturn($queryMock);

        // integerCastExpression() calls DB::connection()->getDriverName()
        $connectionMock = $this->mock(\Illuminate\Database\Connection::class);
        $connectionMock->shouldReceive('getDriverName')
            ->zeroOrMoreTimes()
            ->andReturn('sqlite');

        DB::shouldReceive('connection')
            ->zeroOrMoreTimes()
            ->andReturn($connectionMock);

        // loadProducts141() calls DB::raw() inside the select array
        DB::shouldReceive('raw')
            ->zeroOrMoreTimes()
            ->andReturn('dummy_raw');
    }

    /**
     * Assert each report item has the expected keys.
     */
    private function assertReportStructure(array $report): void
    {
        $this->assertArrayHasKey('codigo', $report);
        $this->assertArrayHasKey('titulo', $report);
        $this->assertArrayHasKey('descricao', $report);
        $this->assertArrayHasKey('rota', $report);
        $this->assertArrayHasKey('quantidade', $report);
    }

    // ─── Without permissions ──────────────────────────────────────────────

    public function testListAvailableReportsWithoutPermissions(): void
    {
        $this->mockProductsTableReturnsEmpty();
        Session::shouldReceive('get')
            ->with('legacy_permissions', [])
            ->once()
            ->andReturn([]);

        $result = $this->service->listAvailableReports(1);

        // 6 static reports (14.2, 14.3, 14.4, 14.5, 14.7, 14.8)
        // 14.1 and 14.6 are skipped because products table is empty
        // POS is not included because permission is absent
        $this->assertCount(6, $result);

        $codigos = array_map(static fn (array $r): string => $r['codigo'], $result);
        $this->assertNotContains('14.1', $codigos);
        $this->assertContains('14.2', $codigos);
        $this->assertContains('14.3', $codigos);
        $this->assertContains('14.4', $codigos);
        $this->assertContains('14.5', $codigos);
        $this->assertNotContains('14.6', $codigos);
        $this->assertContains('14.7', $codigos);
        $this->assertContains('14.8', $codigos);
        $this->assertNotContains('POS', $codigos);

        foreach ($result as $report) {
            $this->assertReportStructure($report);
            $this->assertSame(1, $report['quantidade']);
        }
    }

    // ─── With permissions ─────────────────────────────────────────────────

    public function testListAvailableReportsWithPermissions(): void
    {
        $this->mockProductsTableReturnsEmpty();
        Session::shouldReceive('get')
            ->with('legacy_permissions', [])
            ->once()
            ->andReturn(['reports.changes.view' => true]);

        $result = $this->service->listAvailableReports(1);

        // 6 static reports + 1 POS report = 7
        $this->assertCount(7, $result);

        $codigos = array_map(static fn (array $r): string => $r['codigo'], $result);
        $this->assertContains('14.2', $codigos);
        $this->assertContains('POS', $codigos);

        // Find and verify the POS report structure
        $posReport = array_values(
            array_filter($result, static fn (array $r): bool => $r['codigo'] === 'POS')
        );
        $this->assertCount(1, $posReport);
        $this->assertReportStructure($posReport[0]);
        $this->assertSame('Posição de estoque', $posReport[0]['titulo']);
        $this->assertSame(0, $posReport[0]['quantidade']); // empty products table
    }

    public function testChurchOptionsReturnsCollection(): void
    {
        $builder = Mockery::mock('alias:' . Comum::class);
        $builder->shouldReceive('query')
            ->once()
            ->andReturnSelf();
        $builder->shouldReceive('orderBy')
            ->with('codigo')
            ->once()
            ->andReturnSelf();
        $builder->shouldReceive('get')
            ->with(['id', 'codigo', 'descricao'])
            ->once()
            ->andReturn(collect([
                (object) ['id' => 1, 'codigo' => '001', 'descricao' => 'Igreja Central'],
                (object) ['id' => 2, 'codigo' => '002', 'descricao' => 'Igreja Norte'],
            ]));

        $result = $this->service->churchOptions();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertSame('001', $result->first()->codigo);
    }

    public function testChurchOptionsReturnsItemsWithExpectedKeys(): void
    {
        $builder = Mockery::mock('alias:' . Comum::class);
        $builder->shouldReceive('query')
            ->once()
            ->andReturnSelf();
        $builder->shouldReceive('orderBy')
            ->with('codigo')
            ->once()
            ->andReturnSelf();
        $builder->shouldReceive('get')
            ->with(['id', 'codigo', 'descricao'])
            ->once()
            ->andReturn(collect([
                (object) ['id' => 3, 'codigo' => '003', 'descricao' => 'Igreja Sul'],
            ]));

        $result = $this->service->churchOptions();
        $item = $result->first();

        $this->assertObjectHasProperty('id', $item);
        $this->assertObjectHasProperty('codigo', $item);
        $this->assertObjectHasProperty('descricao', $item);
        $this->assertSame(3, $item->id);
        $this->assertSame('003', $item->codigo);
        $this->assertSame('Igreja Sul', $item->descricao);
    }

    public function testChurchOptionsReturnsEmptyCollectionWhenNoChurches(): void
    {
        $builder = Mockery::mock('alias:' . Comum::class);
        $builder->shouldReceive('query')
            ->once()
            ->andReturnSelf();
        $builder->shouldReceive('orderBy')
            ->with('codigo')
            ->once()
            ->andReturnSelf();
        $builder->shouldReceive('get')
            ->with(['id', 'codigo', 'descricao'])
            ->once()
            ->andReturn(collect());

        $result = $this->service->churchOptions();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
        $this->assertCount(0, $result);
    }

    public function testChurchOptionsAppliesOrderByCodigo(): void
    {
        $builder = Mockery::mock('alias:' . Comum::class);
        $builder->shouldReceive('get')
            ->with(['id', 'codigo', 'descricao'])
            ->once()
            ->andReturn(collect([
                (object) ['id' => 20, 'codigo' => '001', 'descricao' => 'Igreja A'],
                (object) ['id' => 10, 'codigo' => '002', 'descricao' => 'Igreja B'],
                (object) ['id' => 30, 'codigo' => '003', 'descricao' => 'Igreja C'],
            ]));
        $builder->shouldReceive('query')
            ->once()
            ->andReturnSelf();
        $builder->shouldReceive('orderBy')
            ->with('codigo')
            ->once()
            ->andReturnSelf();

        $result = $this->service->churchOptions();
        $codes = $result->pluck('codigo')->toArray();

        $this->assertSame(['001', '002', '003'], $codes);
    }
}
