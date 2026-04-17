<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacyInventoryServiceInterface;
use App\DTO\LegacyInventorySnapshot;
use App\DTO\LegacyModuleSummary;
use Tests\TestCase;

final class LegacyMigrationDashboardTest extends TestCase
{
    public function testDashboardRendersMigrationInventory(): void
    {
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
        $response->assertSee('Resumo do sistema.');
        $response->assertSee('Indicadores rápidos');
        $response->assertSee('Registros contabilizados');
        $response->assertSee('Módulos principais');
        $response->assertSee('Igrejas');
        $response->assertSee('Estrutura');
        $response->assertSee('Produtos');
        $response->assertSee('Inventário');
        $response->assertSee('Relatórios');
        $response->assertSee('Fluxo');
    }
}
