<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacyAuthSessionServiceInterface;
use InvalidArgumentException;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

final class LegacyAuthControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure legacy.bridge middleware does not interfere in tests:
        // import/export on the native session bridge are safe no-ops with
        // SESSION_DRIVER=array, but we keep _enforce_legacy_auth off so the
        // RequireLegacySession guard lets all requests through.
    }

    // ─── showLogin ─────────────────────────────────────────────────────

    public function test_show_login_redirects_to_dashboard_when_already_authenticated(): void
    {
        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isAuthenticated')->once()->andReturn(true);
        });

        $response = $this->get(route('migration.login'));

        $response->assertRedirect(route('migration.dashboard'));
    }

    // ─── showForgotPassword ────────────────────────────────────────────

    public function test_show_forgot_password_redirects_to_dashboard_when_already_authenticated(): void
    {
        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isAuthenticated')->once()->andReturn(true);
        });

        $response = $this->get(route('migration.password.request'));

        $response->assertRedirect(route('migration.dashboard'));
    }

    // ─── login – invalid credentials ───────────────────────────────────

    public function test_login_fails_with_invalid_credentials(): void
    {
        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('attempt')
                ->once()
                ->with('wrong@email.com', 'wrong-password')
                ->andThrow(new RuntimeException('E-mail ou senha inválidos.'));
        });

        $response = $this->post(route('migration.login.store'), [
            'email' => 'wrong@email.com',
            'senha' => 'wrong-password',
        ]);

        $response->assertRedirect(route('migration.login'));
        $response->assertSessionHas('status', 'E-mail ou senha inválidos.');
        $response->assertSessionHas('status_type', 'error');
        $response->assertSessionMissing('usuario_id');
    }

    public function test_login_fails_when_service_throws_unexpected_exception(): void
    {
        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('attempt')
                ->once()
                ->andThrow(new RuntimeException('Conta temporariamente bloqueada.'));
        });

        $response = $this->post(route('migration.login.store'), [
            'email' => 'locked@exemplo.com',
            'senha' => 'segredo',
        ]);

        $response->assertRedirect(route('migration.login'));
        $response->assertSessionHas('status', 'Conta temporariamente bloqueada.');
        $response->assertSessionHas('status_type', 'error');
        $response->assertSessionMissing('usuario_id');
    }

    // ─── login – validation failures ───────────────────────────────────

    public function test_login_fails_when_email_is_empty(): void
    {
        $response = $this->post(route('migration.login.store'), [
            'email' => '',
            'senha' => 'some-password',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_login_fails_when_password_is_empty(): void
    {
        $response = $this->post(route('migration.login.store'), [
            'email' => 'user@example.com',
            'senha' => '',
        ]);

        $response->assertSessionHasErrors(['senha']);
    }

    public function test_login_fails_when_email_is_not_valid(): void
    {
        $response = $this->post(route('migration.login.store'), [
            'email' => 'not-an-email',
            'senha' => 'some-password',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_login_fails_when_both_fields_are_missing(): void
    {
        $response = $this->post(route('migration.login.store'), []);

        $response->assertSessionHasErrors(['email', 'senha']);
    }

    // ─── login – valid credentials ─────────────────────────────────────

    public function test_login_with_valid_credentials_redirects_to_dashboard(): void
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
                    'legacy_permissions' => [],
                ]);
        });

        $response = $this->post(route('migration.login.store'), [
            'email' => 'maria@exemplo.com',
            'senha' => 'segredo',
        ]);

        $response->assertRedirect(route('migration.dashboard'));
        $response->assertSessionHas('usuario_id', 9);
        $response->assertSessionHas('usuario_nome', 'Maria Silva');
        $response->assertSessionHas('usuario_email', 'MARIA@EXEMPLO.COM');
        $response->assertSessionHas('is_admin', false);
        $response->assertSessionHas('status', 'Login realizado com sucesso.');
        $response->assertSessionHas('status_type', 'success');
    }

    // ─── login – redirect_after_login ──────────────────────────────────

    public function test_login_redirects_to_custom_url_when_redirect_after_login_is_set(): void
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
                    'legacy_permissions' => [],
                ]);
        });

        $response = $this->withSession([
            'redirect_after_login' => '/products',
        ])->post(route('migration.login.store'), [
            'email' => 'maria@exemplo.com',
            'senha' => 'segredo',
        ]);

        $response->assertRedirect('/products');
        // Status message is only set on the dashboard redirect, not on custom URLs
        $response->assertSessionMissing('status');
        $response->assertSessionHas('usuario_id', 9);
    }

    public function test_login_ignores_non_relative_redirect_after_login(): void
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
                    'legacy_permissions' => [],
                ]);
        });

        $response = $this->withSession([
            'redirect_after_login' => 'https://evil.com/phish',
        ])->post(route('migration.login.store'), [
            'email' => 'maria@exemplo.com',
            'senha' => 'segredo',
        ]);

        // Non-relative URL should be ignored — redirect to dashboard instead
        $response->assertRedirect(route('migration.dashboard'));
        $response->assertSessionHas('usuario_id', 9);
    }

    // ─── logout ────────────────────────────────────────────────────────

    public function test_logout_clears_legacy_session_and_redirects(): void
    {
        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('logout')->once();
        });

        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'maria@exemplo.com',
        ])->post(route('migration.logout'));

        $response->assertRedirect(route('migration.login'));
        $response->assertSessionHas('status', 'Sessão encerrada.');
        $response->assertSessionHas('status_type', 'success');
        $response->assertSessionMissing('usuario_id');
        $response->assertSessionMissing('usuario_nome');
        $response->assertSessionMissing('usuario_email');
    }

    // ─── login – admin user variations ─────────────────────────────────

    public function test_login_with_admin_user_stores_is_admin_true(): void
    {
        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('attempt')
                ->once()
                ->with('admin@example.com', 'admin123')
                ->andReturn([
                    'id' => 1,
                    'nome' => 'Admin User',
                    'email' => 'ADMIN@EXAMPLE.COM',
                    'comum_id' => null,
                    'administracao_id' => null,
                    'administracoes_permitidas' => [1, 2, 3, 4, 5],
                    'is_admin' => true,
                    'legacy_permissions' => [
                        'products.view' => true,
                        'users.view' => true,
                        'users.permissions.manage' => true,
                    ],
                ]);
        });

        $response = $this->post(route('migration.login.store'), [
            'email' => 'admin@example.com',
            'senha' => 'admin123',
        ]);

        $response->assertRedirect(route('migration.dashboard'));
        $response->assertSessionHas('usuario_id', 1);
        $response->assertSessionHas('is_admin', true);
        $response->assertSessionHas('administracoes_permitidas', [1, 2, 3, 4, 5]);
    }

    public function test_login_preserves_existing_legacy_permissions_from_session(): void
    {
        $this->mock(LegacyAuthSessionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('attempt')
                ->once()
                ->with('user@example.com', 'segredo')
                ->andReturn([
                    'id' => 15,
                    'nome' => 'Test User',
                    'email' => 'USER@EXAMPLE.COM',
                    'comum_id' => 5,
                    'administracao_id' => 3,
                    'administracoes_permitidas' => [3],
                    'is_admin' => false,
                    // No 'legacy_permissions' key — controller falls back to session
                ]);
        });

        $response = $this->withSession([
            'legacy_permissions' => [
                'products.view' => true,
                'users.view' => false,
            ],
        ])->post(route('migration.login.store'), [
            'email' => 'user@example.com',
            'senha' => 'segredo',
        ]);

        // attempt() didn't include 'legacy_permissions', so session value is preserved
        $response->assertRedirect(route('migration.dashboard'));
        $response->assertSessionHas('legacy_permissions', [
            'products.view' => true,
            'users.view' => false,
        ]);
    }
}
