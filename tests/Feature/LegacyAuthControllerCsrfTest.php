<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacyAuthSessionServiceInterface;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Tests that POST /login (migration.login.store) rejects requests without
 * a valid CSRF token (returning 419), and that a request with a valid token
 * passes the CSRF check.
 *
 * Laravel's PreventRequestForgery::runningUnitTests() bypasses CSRF when
 * APP_ENV=testing. We temporarily set the env to 'production' to verify the
 * 419 rejection path, then restore it for valid-token tests.
 */
final class LegacyAuthControllerCsrfTest extends TestCase
{
    private ?string $savedEnv = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->savedEnv = $this->app->environment();
    }

    protected function tearDown(): void
    {
        if ($this->savedEnv !== null) {
            $this->app->instance('env', $this->savedEnv);
        }
        parent::tearDown();
    }

    /**
     * Temporarily set the app environment so that runningUnitTests() returns
     * false and the CSRF middleware runs its full verification logic.
     */
    private function enableCsrfProtection(): void
    {
        $this->app->instance('env', 'production');
    }

    /**
     * Restore the testing environment (CSRF bypass through runningUnitTests).
     */
    private function restoreCsrfBypass(): void
    {
        if ($this->savedEnv !== null) {
            $this->app->instance('env', $this->savedEnv);
        }
    }

    public function test_login_rejects_request_without_csrf_token(): void
    {
        $this->enableCsrfProtection();

        $response = $this->post(route('migration.login.store'), [
            'email' => 'user@example.com',
            'senha' => 'some-password',
        ]);

        $response->assertStatus(419);
    }

    public function test_login_allows_request_with_valid_csrf_token(): void
    {
        $this->enableCsrfProtection();

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

        // Generate a valid session CSRF token and send it with the request
        $this->app['session']->start();
        $token = $this->app['session']->token();

        $response = $this->post(route('migration.login.store'), [
            '_token' => $token,
            'email' => 'maria@exemplo.com',
            'senha' => 'segredo',
        ]);

        // Should NOT be blocked by CSRF (may pass through to controller)
        $this->assertNotSame(419, $response->getStatusCode(), 'Request with valid CSRF token should not return 419');
    }

    /**
     * Sanity check: with the default test bypass, CSRF does not block the login request.
     */
    public function test_login_not_blocked_when_csrf_bypass_active(): void
    {
        $this->restoreCsrfBypass();

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

        $this->assertNotSame(419, $response->getStatusCode(), 'Default test bypass should not return 419');
    }
}
