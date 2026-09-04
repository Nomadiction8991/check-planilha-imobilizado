<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyChurchBrowserServiceInterface;
use App\Contracts\LegacyNavigationServiceInterface;
use App\Contracts\LegacyPermissionServiceInterface;
use App\DTO\ChurchFilters;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\TestCase;

final class LegacyAccessibilityLayoutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(LegacyChurchBrowserServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('paginate')->andReturn(new LengthAwarePaginator(
                items: collect(),
                total: 0,
                perPage: 20,
                currentPage: 1,
                options: ['path' => '/churches'],
            ));
            $mock->shouldReceive('countAll')->andReturn(0);
            $mock->shouldReceive('administrationOptions')->andReturn(collect());
        });

        $this->mock(LegacyPermissionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('currentPermissions')->andReturn([
                'churches.view' => true,
            ]);
            $mock->shouldReceive('can')->with('churches.view')->andReturnTrue();
        });

        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('currentUser')->andReturn([
                'id' => 9,
                'nome' => 'Maria Silva',
                'email' => 'maria@example.com',
                'comum_id' => 7,
                'administracao_id' => 4,
                'is_admin' => true,
            ]);
            $mock->shouldReceive('currentChurch')->andReturn([
                'id' => 7,
                'codigo' => '12-3456',
                'descricao' => 'Central Cuiabá',
            ]);
            $mock->shouldReceive('availableChurches')->andReturn(collect());
            $mock->shouldReceive('availableAdministrations')->andReturn(collect());
            $mock->shouldReceive('filterPinStates')->andReturn([]);
        });

        $this->mock(LegacyNavigationServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('navigation')->andReturn([]);
        });
    }

    public function testAdministrativeLayoutProvidesSkipLinkAndKeyboardFocusStyles(): void
    {
        $this->app->instance(
            LegacyChurchBrowserServiceInterface::class,
            new class implements LegacyChurchBrowserServiceInterface
            {
                public function paginate(ChurchFilters $filters): LengthAwarePaginator
                {
                    return new LengthAwarePaginator(
                        items: collect(),
                        total: 0,
                        perPage: 20,
                        currentPage: 1,
                        options: ['path' => '/churches'],
                    );
                }

                public function countAll(): int
                {
                    return 0;
                }

                public function administrationOptions(): Collection
                {
                    return collect();
                }
            },
        );

        $this->mock(LegacyPermissionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('currentPermissions')->andReturn([
                'churches.view' => true,
            ]);
            $mock->shouldReceive('can')->with('churches.view')->andReturnTrue();
        });

        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'maria@example.com',
            'comum_id' => 7,
            'administracao_id' => 4,
            'is_admin' => true,
            'legacy_permissions' => ['churches.view' => true],
        ])->get(route('migration.churches.index'));

        $response->assertOk();
        $response->assertSee('<a class="skip-link" href="#main-content">Ir para o conteúdo principal</a>', false);
        $response->assertSee('<main class="shell" id="main-content" tabindex="-1">', false);
        $response->assertSee('.skip-link:focus-visible', false);
        $response->assertSee('a:focus-visible,', false);
        $response->assertSee('button:focus-visible,', false);
        $response->assertSee('input:focus-visible,', false);
        $response->assertSee('select:focus-visible,', false);
        $response->assertSee('textarea:focus-visible', false);
        $response->assertSee('@media (prefers-reduced-motion: reduce)', false);
    }
}
