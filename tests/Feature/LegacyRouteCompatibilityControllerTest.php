<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\LegacyPermissionServiceInterface;
use Mockery\MockInterface;
use Tests\TestCase;

final class LegacyRouteCompatibilityControllerTest extends TestCase
{
    private const string CSRF_TOKEN = 'test-csrf-token-value';

    protected function setUp(): void
    {
        parent::setUp();
        $this->sessionData = [];
    }

    private function dumpSessionData(string $label): void
    {
        fwrite(STDERR, "[{$label}] sessionData keys: " . implode(', ', array_keys((array)$this->sessionData)) . "\n");
    }

    /**
     * @return array<string, bool|int|string|null>
     */
    private function authSession(): array
    {
        return [
            '_enforce_legacy_auth' => true,
            'usuario_id' => 9,
            'usuario_nome' => 'Maria Silva',
            'usuario_email' => 'MARIA@EXEMPLO.COM',
            'comum_id' => 7,
            'is_admin' => true,
        ];
    }

    /**
     * @return array<string, bool|int|string|null>
     */
    private function authSessionWithToken(): array
    {
        return $this->authSession() + ['_token' => self::CSRF_TOKEN];
    }

    private function mockPermissionCan(): void
    {
        $this->mock(LegacyPermissionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('currentPermissions')->andReturn([]);
            $mock->shouldReceive('can')->andReturnTrue();
        });
    }

    // ─── Churches ────────────────────────────────────────────────────

    public function testChurchesEditGetRedirectsToCanonicalEditPage(): void
    {
        $this->mockPermissionCan();

        $response = $this->withSession($this->authSession())
            ->get('/churches/edit?id=5');

        $response->assertRedirect('/churches/5/edit');
    }

    public function testChurchesEditGetRedirectsWithErrorWhenNoId(): void
    {
        $this->mockPermissionCan();

        $response = $this->withSession($this->authSession())
            ->get('/churches/edit');

        $response->assertRedirect('/churches');
        $response->assertSessionHas('status', 'Igreja não informada para edição.');
        $response->assertSessionHas('status_type', 'error');
    }

    public function testChurchesUpdatePostRedirectsToCanonicalUpdateRoute(): void
    {
        $this->mockPermissionCan();

        $response = $this->withSession($this->authSessionWithToken())
            ->post('/churches/edit', ['id' => 5, '_token' => self::CSRF_TOKEN]);

        $response->assertStatus(307);
        $response->assertRedirect('/churches/5');
    }

    public function testChurchesUpdatePostRedirectsWithErrorWhenNoId(): void
    {
        $this->mockPermissionCan();

        $response = $this->withSession($this->authSessionWithToken())
            ->post('/churches/edit', ['_token' => self::CSRF_TOKEN]);

        $response->assertRedirect('/churches');
        $response->assertSessionHas('status', 'Igreja não informada para atualização.');
        $response->assertSessionHas('status_type', 'error');
    }

    // ─── Departments ─────────────────────────────────────────────────

    public function testDepartmentsEditGetRedirectsToCanonicalEditPage(): void
    {
        $this->mockPermissionCan();

        $response = $this->withSession($this->authSession())
            ->get('/departments/edit?id=3');

        $response->assertRedirect('/departments/3/edit');
    }

    public function testDepartmentsEditGetRedirectsWithErrorWhenNoId(): void
    {
        $this->mockPermissionCan();

        $response = $this->withSession($this->authSession())
            ->get('/departments/edit');

        $response->assertRedirect('/departments');
        $response->assertSessionHas('status', 'Dependência não informada para edição.');
        $response->assertSessionHas('status_type', 'error');
    }

    public function testDepartmentsUpdatePostRedirectsToCanonicalUpdateRoute(): void
    {
        $this->mockPermissionCan();

        $response = $this->withSession($this->authSessionWithToken())
            ->post('/departments/edit', ['id' => 3, '_token' => self::CSRF_TOKEN]);

        $response->assertStatus(307);
        $response->assertRedirect('/departments/3');
    }

    public function testDepartmentsUpdatePostRedirectsWithErrorWhenNoId(): void
    {
        $this->mockPermissionCan();

        $response = $this->withSession($this->authSessionWithToken())
            ->post('/departments/edit', ['_token' => self::CSRF_TOKEN]);

        $response->assertRedirect('/departments');
        $response->assertSessionHas('status', 'Dependência não informada para atualização.');
        $response->assertSessionHas('status_type', 'error');
    }

    public function testDepartmentsDeletePostRedirectsToCanonicalDestroyRoute(): void
    {
        $this->mockPermissionCan();

        $response = $this->withSession($this->authSessionWithToken())
            ->post('/departments/delete', ['id' => 3, '_token' => self::CSRF_TOKEN]);

        $response->assertStatus(307);
        $response->assertRedirect('/departments/3/delete');
    }

    public function testDepartmentsDeletePostRedirectsWithErrorWhenNoId(): void
    {
        $this->mockPermissionCan();

        $response = $this->withSession($this->authSessionWithToken())
            ->post('/departments/delete', ['_token' => self::CSRF_TOKEN]);

        $response->assertRedirect('/departments');
        $response->assertSessionHas('status', 'Dependência não informada para exclusão.');
        $response->assertSessionHas('status_type', 'error');
    }

    // ─── Asset Types ─────────────────────────────────────────────────

    public function testAssetTypesDeletePostRedirectsToCanonicalDestroyRoute(): void
    {
        $this->mockPermissionCan();

        $response = $this->withSession($this->authSessionWithToken())
            ->post('/asset-types/delete', ['id' => 7, '_token' => self::CSRF_TOKEN]);

        $response->assertStatus(307);
        $response->assertRedirect('/asset-types/7/delete');
    }

    public function testAssetTypesDeletePostRedirectsWithErrorWhenNoId(): void
    {
        $this->mockPermissionCan();

        $response = $this->withSession($this->authSessionWithToken())
            ->post('/asset-types/delete', ['_token' => self::CSRF_TOKEN]);

        $response->assertRedirect('/asset-types');
        $response->assertSessionHas('status', 'Tipo de bem não informado para exclusão.');
        $response->assertSessionHas('status_type', 'error');
    }

    // ─── Auth redirection tests ──────────────────────────────────────

    public function testChurchesEditGetRedirectsGuestsToLogin(): void
    {
        $this->mock(LegacyPermissionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('can')->andReturnFalse();
        });

        $response = $this->withSession(['_enforce_legacy_auth' => true])
            ->get('/churches/edit');

        $response->assertRedirect(route('migration.login'));
    }

    public function testDepartmentsEditGetRedirectsGuestsToLogin(): void
    {
        $this->mock(LegacyPermissionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('can')->andReturnFalse();
        });

        $response = $this->withSession(['_enforce_legacy_auth' => true])
            ->get('/departments/edit');

        $response->assertRedirect(route('migration.login'));
    }

    public function testDepartmentsDeletePostRedirectsGuestsToLogin(): void
    {
        $this->mock(LegacyPermissionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('can')->andReturnFalse();
        });

        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            '_token' => self::CSRF_TOKEN,
        ])->post('/departments/delete', ['_token' => self::CSRF_TOKEN]);

        $response->assertRedirect(route('migration.login'));
    }

    public function testAssetTypesDeletePostRedirectsGuestsToLogin(): void
    {
        $this->mock(LegacyPermissionServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('can')->andReturnFalse();
        });

        $response = $this->withSession([
            '_enforce_legacy_auth' => true,
            '_token' => self::CSRF_TOKEN,
        ])->post('/asset-types/delete', ['_token' => self::CSRF_TOKEN]);

        $response->assertRedirect(route('migration.login'));
    }
}
