<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyNavigationServiceInterface;
use App\Contracts\LegacyPermissionServiceInterface;
use Illuminate\Support\Facades\Schema;
use Mockery\MockInterface;
use Tests\TestCase;

final class PublicAccessNavigationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('currentUser')->andReturnUsing(static function (): ?array {
                $session = session();
                if ((int) $session->get('usuario_id', 0) <= 0) {
                    return null;
                }

                return [
                    'nome' => (string) $session->get('usuario_nome', ''),
                    'email' => (string) $session->get('usuario_email', ''),
                ];
            });
            $mock->shouldReceive('currentChurch')->andReturn(null);
            $mock->shouldReceive('availableChurches')->andReturn(collect());
            $mock->shouldReceive('availableAdministrations')->andReturn(collect());
            $mock->shouldReceive('filterPinStates')->andReturn([]);
        });
        $this->mock(LegacyNavigationServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('navigation')->andReturn([]);
        });
        $this->mock(LegacyPermissionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('currentPermissions')->andReturn([]);
        });
    }

    private function attachSessionStore(): void
    {
        if (!app('session')->isStarted()) {
            app('session')->start();
        }

        app('request')->setLaravelSession(app('session.store'));
    }

    public function testPublicSessionShowsPublicExitInsteadOfAdministrativeLogout(): void
    {
        $this->withSession([
            'public_acesso' => true,
            'public_comum_id' => 15,
        ]);
        $this->attachSessionStore();
        $this->mockLayoutTables();

        $response = $this->view('layouts.migration', [
            'legacySessionUser' => [],
            'legacyNavigation' => [],
            'legacyPermissions' => [],
        ]);

        $html = (string) $response;

        self::assertStringContainsString('Sair do acesso público', $html);
        self::assertStringContainsString('action="' . route('public.access.logout') . '"', $html);
        self::assertStringNotContainsString('action="' . route('migration.logout') . '"', $html);
        self::assertSame(2, substr_count($html, 'action="' . route('public.access.logout') . '"'));
    }

    public function testAdministrativeSessionKeepsAdministrativeExitWithoutPublicExit(): void
    {
        $this->withSession([
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'maria@example.com',
            'public_acesso' => false,
        ]);
        $this->attachSessionStore();
        $this->mockLayoutTables();

        $response = $this->view('layouts.migration', [
            'legacySessionUser' => [
                'nome' => 'Maria Silva',
                'email' => 'maria@example.com',
            ],
            'legacyNavigation' => [],
            'legacyPermissions' => [],
        ]);

        $html = (string) $response;

        self::assertStringContainsString('action="' . route('migration.logout') . '"', $html);
        self::assertStringNotContainsString('Sair do acesso público', $html);
    }

    private function mockLayoutTables(): void
    {
        if (!Schema::hasTable('configuracoes')) {
            Schema::create('configuracoes', static function ($table): void {
                $table->id();
                $table->text('menu_order')->nullable();
            });
        }
    }
}
