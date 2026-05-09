<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyInventoryServiceInterface;
use App\DTO\LegacyInventorySnapshot;
use Mockery\MockInterface;
use Tests\TestCase;

final class LegacyAuthFlowTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->destroyNativeSession();

        parent::tearDown();
    }

    public function testLoginPageRenders(): void
    {
        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isAuthenticated')->andReturn(false);
        });

        $response = $this->get(route('migration.login'));

        $response->assertOk();
        $response->assertSee('Acesso restrito');
    }

    public function testProtectedRouteRedirectsToLoginWhenAuthenticationIsEnforced(): void
    {
        $response = $this->withSession(['_enforce_legacy_auth' => true])
            ->get(route('migration.dashboard'));

        $response->assertRedirect(route('migration.login'));
    }

    public function testLoginStoresLegacySessionAndRedirects(): void
    {
        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
                $mock->shouldReceive('attempt')
                ->once()
                ->with('maria@exemplo.com', 'segredo')
                ->andReturn([
                    'id' => 9,
                    'nome' => 'Maria Silva',
                    'email' => 'MARIA@EXEMPLO.COM',
                    'comum_id' => null,
                    'administracao_id' => 4,
                    'administracoes_permitidas' => [4, 8],
                    'is_admin' => false,
                    'legacy_permissions' => [
                        'products.view' => true,
                        'users.view' => true,
                        'users.permissions.manage' => false,
                    ],
                ]);
        });

        $response = $this->post(route('migration.login.store'), [
            'email' => 'maria@exemplo.com',
            'senha' => 'segredo',
        ]);

        $response->assertRedirect(route('migration.dashboard'));
        $response->assertSessionHas('status', 'Login realizado com sucesso.');
        $response->assertSessionHas('administracao_id', 4);
        $response->assertSessionHas('administracoes_permitidas', [4, 8]);
        $response->assertSessionHas('legacy_permissions', [
            'products.view' => true,
            'users.view' => true,
            'users.permissions.manage' => false,
        ]);
    }

    public function testLegacyNativeSessionAuthenticatesLaravelRouteInHybridMode(): void
    {
        $sessionId = 'legacy-bridge-auth';

        $this->writeNativeSession($sessionId, [
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'administracao_id' => 4,
            'administracoes_permitidas' => [4, 8],
            'is_admin' => false,
        ]);

        $this->mock(LegacyInventoryServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('buildSnapshot')->once()->andReturn(
                new LegacyInventorySnapshot(
                    legacyRootPath: base_path(),
                    legacyPublicUrl: url('/'),
                    databaseReachable: true,
                    databaseDriver: 'mysql',
                    databaseName: 'check-planilha',
                    databaseError: null,
                    architectureCounts: [
                        'controllers' => 0,
                        'services' => 0,
                        'repositories' => 0,
                        'views' => 0,
                    ],
                    modules: [],
                )
            );
        });
        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('currentUser')->andReturn([
                'id' => 9,
                'nome' => 'Maria Silva',
                'email' => 'MARIA@EXEMPLO.COM',
                'comum_id' => 7,
                'administracao_id' => 4,
                'administracoes_permitidas' => [4, 8],
                'is_admin' => false,
            ]);
            $mock->shouldReceive('currentChurch')->andReturn([
                'id' => 7,
                'codigo' => '12-3456',
                'descricao' => 'Central Cuiabá',
            ]);
            $mock->shouldReceive('availableChurches')->andReturn(collect([
                (object) ['id' => 7, 'codigo' => '12-3456', 'descricao' => 'Central Cuiabá'],
            ]));
        });

        $response = $this->withCookie(session_name(), $sessionId)
            ->get(route('migration.dashboard'));

        $response->assertOk();
        $response->assertSessionHas('usuario_id', 9);
        $response->assertSessionHas('administracoes_permitidas', [4, 8]);
    }

    public function testLaravelLoginExportsAuthKeysToLegacyNativeSession(): void
    {
        $sessionId = 'legacy-bridge-login';

        $this->writeNativeSession($sessionId, [
            'usuario_id' => 9,
            'usuario_nome' => 'Usuario Antigo',
            'usuario_email' => 'ANTIGO@EXEMPLO.COM',
            'comum_id' => 3,
            'administracao_id' => 4,
            'is_admin' => true,
        ]);

        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
                $mock->shouldReceive('attempt')
                ->once()
                ->with('maria@exemplo.com', 'segredo')
                ->andReturn([
                    'id' => 9,
                    'nome' => 'Maria Silva',
                    'email' => 'MARIA@EXEMPLO.COM',
                    'comum_id' => null,
                    'administracao_id' => 4,
                    'administracoes_permitidas' => [4, 8],
                    'is_admin' => false,
                    'legacy_permissions' => [
                        'products.view' => true,
                        'users.view' => true,
                    ],
                ]);
        });

        $response = $this->withCookie(session_name(), $sessionId)
            ->post(route('migration.login.store'), [
                'email' => 'maria@exemplo.com',
                'senha' => 'segredo',
            ]);

        $response->assertRedirect(route('migration.dashboard'));

        $nativeSession = $this->readNativeSession($sessionId);

        self::assertSame(9, $nativeSession['usuario_id'] ?? null);
        self::assertSame('Maria Silva', $nativeSession['usuario_nome'] ?? null);
        self::assertSame('MARIA@EXEMPLO.COM', $nativeSession['usuario_email'] ?? null);
        self::assertNull($nativeSession['comum_id'] ?? null);
        self::assertSame(4, $nativeSession['administracao_id'] ?? null);
        self::assertSame([4, 8], $nativeSession['administracoes_permitidas'] ?? null);
        self::assertFalse((bool) ($nativeSession['is_admin'] ?? true));
        self::assertSame([
            'products.view' => true,
            'users.view' => true,
        ], $nativeSession['legacy_permissions'] ?? null);
    }

    public function testSwitchChurchUpdatesSession(): void
    {
        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('switchChurch')->once()->with(11);
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
                (object) ['id' => 11, 'codigo' => '12-7890', 'descricao' => 'Várzea Grande'],
            ]));
        });

        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'administracao_id' => 4,
        ])->post(route('migration.session.church'), [
            'comum_id' => 11,
            'redirect_to' => '/products',
        ]);

        $response->assertRedirect('/products');
        $response->assertSessionHas('status', 'Igreja ativa atualizada.');
    }

    public function testSwitchChurchRejectsChurchOutsideUserScope(): void
    {
        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('switchChurch')
                ->once()
                ->with(11)
                ->andThrow(new \RuntimeException('Igreja fora do escopo permitido.'));
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
                (object) ['id' => 11, 'codigo' => '12-7890', 'descricao' => 'Várzea Grande'],
            ]));
        });

        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'administracao_id' => 4,
        ])->post(route('migration.session.church'), [
            'comum_id' => 11,
            'redirect_to' => '/products',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Igreja fora do escopo permitido.');
        $response->assertSessionHas('status_type', 'error');
        $response->assertSessionHas('comum_id', 7);
    }

    public function testPublicLogoutUsesPostAndClearsPublicSession(): void
    {
        $response = $this->withSession([
            'public_acesso' => true,
            'public_planilha_id' => 15,
            'public_comum_id' => 15,
            'public_comum' => 'Central Cuiabá',
        ])->post(route('public.access.logout'));

        $response->assertRedirect(route('migration.login'));
        $response->assertSessionMissing('public_acesso');
        $response->assertSessionMissing('public_planilha_id');
        $response->assertSessionMissing('public_comum_id');
        $response->assertSessionMissing('public_comum');
    }

    public function testAdminRouteRedirectsNonAdminUser(): void
    {
        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'administracao_id' => 4,
            'is_admin' => false,
        ])->get(route('migration.users.index'));

        $response->assertRedirect(route('migration.dashboard'));
        $response->assertSessionHas('status', 'Seu perfil não tem permissão para executar esta ação.');
    }

    public function testPermissionProtectedRouteRedirectsNonAdminUser(): void
    {
        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'administracao_id' => 4,
            'is_admin' => false,
        ])->post(route('migration.spreadsheets.errors.resolve', ['erro' => 99]), [
            'resolvido' => true,
        ]);

        $response->assertRedirect(route('migration.dashboard'));
        $response->assertSessionHas('status', 'Seu perfil não tem permissão para executar esta ação.');
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function writeNativeSession(string $sessionId, array $payload): void
    {
        $this->prepareNativeSessionStorage();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        session_id($sessionId);
        session_start();
        $_SESSION = $payload;
        session_write_close();
    }

    /**
     * @return array<string, mixed>
     */
    private function readNativeSession(string $sessionId): array
    {
        $this->prepareNativeSessionStorage();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        session_id($sessionId);
        session_start();
        $payload = $_SESSION;
        session_write_close();

        return is_array($payload) ? $payload : [];
    }

    private function destroyNativeSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];
        session_destroy();
        session_write_close();
    }

    private function prepareNativeSessionStorage(): void
    {
        ini_set('session.save_path', sys_get_temp_dir());
    }
}
