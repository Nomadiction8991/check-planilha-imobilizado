<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Contracts\LegacyPermissionServiceInterface;
use App\Http\Middleware\RequireLegacyPermission;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

final class RequireLegacyPermissionTest extends TestCase
{
    // ─── Bypass (testing without _enforce_legacy_auth) ────────────────

    public function testBypassesPermissionCheckInTestingWithoutEnforceAuth(): void
    {
        $permissions = $this->createMock(LegacyPermissionServiceInterface::class);
        $permissions->expects($this->never())->method('can');

        $middleware = new RequireLegacyPermission($permissions);
        $request = $this->createRequest([]);

        $response = $middleware->handle($request, static fn (): Response => new Response('OK', 200), 'manage_products');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    // ─── Not authenticated (no usuario_id) ────────────────────────────

    public function testRedirectsToLoginWhenNotAuthenticated(): void
    {
        $permissions = $this->createMock(LegacyPermissionServiceInterface::class);
        $permissions->expects($this->never())->method('can');

        $middleware = new RequireLegacyPermission($permissions);
        $request = $this->createRequest(['_enforce_legacy_auth' => true]);

        $response = $middleware->handle($request, static fn (): Response => new Response('N/A', 200), 'manage_products');

        self::assertTrue($response->isRedirect());
        self::assertSame(route('migration.login'), $response->getTargetUrl());
    }

    public function testRedirectsToLoginWhenUsuarioIdIsZero(): void
    {
        $permissions = $this->createMock(LegacyPermissionServiceInterface::class);
        $permissions->expects($this->never())->method('can');

        $middleware = new RequireLegacyPermission($permissions);
        $request = $this->createRequest([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 0,
        ]);

        $response = $middleware->handle($request, static fn (): Response => new Response('N/A', 200), 'manage_products');

        self::assertTrue($response->isRedirect());
        self::assertSame(route('migration.login'), $response->getTargetUrl());
    }

    public function testRedirectsToLoginWhenUsuarioIdIsNegative(): void
    {
        $permissions = $this->createMock(LegacyPermissionServiceInterface::class);
        $permissions->expects($this->never())->method('can');

        $middleware = new RequireLegacyPermission($permissions);
        $request = $this->createRequest([
            '_enforce_legacy_auth' => true,
            'usuario_id' => -1,
        ]);

        $response = $middleware->handle($request, static fn (): Response => new Response('N/A', 200), 'manage_products');

        self::assertTrue($response->isRedirect());
        self::assertSame(route('migration.login'), $response->getTargetUrl());
    }

    // ─── Permission denied ────────────────────────────────────────────

    public function testRedirectsToDashboardWhenPermissionDenied(): void
    {
        $permissions = $this->createMock(LegacyPermissionServiceInterface::class);
        $permissions->expects($this->once())
            ->method('can')
            ->with('manage_products')
            ->willReturn(false);

        $middleware = new RequireLegacyPermission($permissions);
        $request = $this->createRequest([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 15,
        ]);

        $response = $middleware->handle($request, static fn (): Response => new Response('N/A', 200), 'manage_products');

        self::assertTrue($response->isRedirect());
        self::assertSame(route('migration.dashboard'), $response->getTargetUrl());
        self::assertSame(
            'Seu perfil não tem permissão para executar esta ação.',
            session('status'),
        );
        self::assertSame('error', session('status_type'));
    }

    public function testRedirectsToDashboardWhenPermissionDeniedWithDifferentAbility(): void
    {
        $permissions = $this->createMock(LegacyPermissionServiceInterface::class);
        $permissions->expects($this->once())
            ->method('can')
            ->with('view_reports')
            ->willReturn(false);

        $middleware = new RequireLegacyPermission($permissions);
        $request = $this->createRequest([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 42,
        ]);

        $response = $middleware->handle($request, static fn (): Response => new Response('N/A', 200), 'view_reports');

        self::assertTrue($response->isRedirect());
        self::assertSame(route('migration.dashboard'), $response->getTargetUrl());
        self::assertSame(
            'Seu perfil não tem permissão para executar esta ação.',
            session('status'),
        );
        self::assertSame('error', session('status_type'));
    }

    // ─── Permission granted ───────────────────────────────────────────

    public function testPassesThroughWhenPermissionGranted(): void
    {
        $permissions = $this->createMock(LegacyPermissionServiceInterface::class);
        $permissions->expects($this->once())
            ->method('can')
            ->with('manage_products')
            ->willReturn(true);

        $middleware = new RequireLegacyPermission($permissions);
        $request = $this->createRequest([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 15,
        ]);

        $response = $middleware->handle($request, static fn (): Response => new Response('OK', 200), 'manage_products');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testPassesThroughWhenPermissionGrantedForDifferentAbility(): void
    {
        $permissions = $this->createMock(LegacyPermissionServiceInterface::class);
        $permissions->expects($this->once())
            ->method('can')
            ->with('view_reports')
            ->willReturn(true);

        $middleware = new RequireLegacyPermission($permissions);
        $request = $this->createRequest([
            '_enforce_legacy_auth' => true,
            'usuario_id' => 7,
        ]);

        $response = $middleware->handle($request, static fn (): Response => new Response('OK', 200), 'view_reports');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    // ─── Helpers ──────────────────────────────────────────────────────

    private function createRequest(array $sessionData): Request
    {
        $request = Request::create('/some-protected-route', 'GET');

        $session = $this->app->make('session')->driver();
        $session->setId('test-session-' . bin2hex(random_bytes(4)));

        foreach ($sessionData as $key => $value) {
            $session->put($key, $value);
        }

        $request->setLaravelSession($session);

        return $request;
    }
}
