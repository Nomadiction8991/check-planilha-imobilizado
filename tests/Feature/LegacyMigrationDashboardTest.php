<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacyInventoryServiceInterface;
use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyPermissionServiceInterface;
use App\DTO\LegacyInventorySnapshot;
use App\DTO\LegacyModuleSummary;
use Tests\TestCase;

final class LegacyMigrationDashboardTest extends TestCase
{
    public function testDashboardRendersMigrationInventory(): void
    {
        $this->mock(LegacyAuthSessionServiceInterface::class, function ($mock): void {
            $mock->shouldReceive('currentUser')->andReturn([
                'id' => 9,
                'nome' => 'Maria Silva',
                'email' => 'MARIA@EXEMPLO.COM',
                'comum_id' => 7,
                'is_admin' => false,
            ]);
            $mock->shouldReceive('currentChurch')->andReturn([
                'id' => 7,
                'codigo' => '12-3456',
                'descricao' => 'Central Cuiabá',
            ]);
            $mock->shouldReceive('availableChurches')->andReturn(collect([
                (object) ['id' => 7, 'codigo' => '12-3456', 'descricao' => 'Central Cuiabá'],
                (object) ['id' => 8, 'codigo' => '98-7654', 'descricao' => 'Filial Várzea'],
            ]));
        });
        $this->mock(LegacyPermissionServiceInterface::class, function ($mock): void {
            $mock->shouldReceive('currentPermissions')->andReturn([
                'products.view' => true,
                'products.edit' => true,
                'reports.view' => true,
                'spreadsheets.import' => true,
                'churches.view' => true,
                'departments.view' => true,
                'asset-types.view' => true,
                'administrations.view' => true,
                'users.view' => true,
                'audits.view' => true,
            ]);
        });
        $this->app->instance(
            LegacyInventoryServiceInterface::class,
            new class implements LegacyInventoryServiceInterface
            {
                public function buildSnapshot(): LegacyInventorySnapshot
                {
                    return new LegacyInventorySnapshot(
                        legacyRootPath: '/tmp/legacy',
                        legacyPublicUrl: 'http://localhost',
                        databaseReachable: true,
                        databaseDriver: 'mysql',
                        databaseName: 'checkplanilha',
                        databaseError: null,
                        architectureCounts: [
                            'controllers' => 12,
                            'services' => 7,
                            'repositories' => 6,
                            'views' => 40,
                        ],
                        modules: [
                            new LegacyModuleSummary(
                                key: 'churches',
                                title: 'Igrejas',
                                description: 'Cadastro principal de comuns.',
                                category: 'Estrutura',
                                tone: 'structure',
                                legacyPath: '/churches',
                                target: 'app/Http/Controllers/ChurchController.php',
                                records: 15,
                            ),
                            new LegacyModuleSummary(
                                key: 'products',
                                title: 'Produtos',
                                description: 'Inventário de bens.',
                                category: 'Inventário',
                                tone: 'inventory',
                                legacyPath: '/products/view',
                                target: 'app/Http/Controllers/LegacyProductController.php',
                                records: 120,
                            ),
                            new LegacyModuleSummary(
                                key: 'reports',
                                title: 'Relatórios',
                                description: 'Formulários 14.x.',
                                category: 'Fluxo',
                                tone: 'flow',
                                legacyPath: '/reports',
                                target: 'app/Http/Controllers/LegacyReportController.php',
                                records: null,
                            ),
                        ],
                    );
                }
            }
        );

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Dashboard do sistema.');
        $response->assertSee('Indicadores rápidos');
        $response->assertSee('Total de registros');
        $response->assertSee('Igrejas');
        $response->assertSee('Produtos');
        $response->assertSee('Dependências');
        $response->assertSee('Tipos de bem');
        $response->assertSee('Usuários');
        $response->assertSee('Auditoria');
        $response->assertSee('Indicadores por igreja');
        $response->assertSee('Central Cuiabá');
        $response->assertSee('Filial Várzea');
    }
}
