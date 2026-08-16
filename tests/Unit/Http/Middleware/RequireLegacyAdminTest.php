<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\RequireLegacyAdmin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

final class RequireLegacyAdminTest extends TestCase
{
    private const DUMMY_RESPONSE = 'ALLOWED';

    public function testAllowsRequestInTestingWithoutEnforcement(): void
    {
        $middleware = new RequireLegacyAdmin();
        $request = Request::create('/admin', 'GET');
        $request->setLaravelSession($this->app['session']->driver());

        $result = $middleware->handle($request, fn (): Response => new Response(self::DUMMY_RESPONSE));

        self::assertInstanceOf(Response::class, $result);
        self::assertSame(self::DUMMY_RESPONSE, $result->getContent());
    }

    public function testRedirectsToLoginWhenSessionHasNoUser(): void
    {
        $middleware = new RequireLegacyAdmin();
        $request = Request::create('/admin', 'GET');
        $request->setLaravelSession($this->app['session']->driver());
        $request->session()->put('_enforce_legacy_auth', true);

        $result = $middleware->handle($request, fn (): Response => new Response(self::DUMMY_RESPONSE));

        self::assertInstanceOf(RedirectResponse::class, $result);
        /** @var RedirectResponse $result */
        self::assertSame(route('migration.login'), $result->getTargetUrl());
    }

    public function testRedirectsToLoginWhenUsuarioIdIsZero(): void
    {
        $middleware = new RequireLegacyAdmin();
        $request = Request::create('/admin', 'GET');
        $request->setLaravelSession($this->app['session']->driver());
        $request->session()->put('_enforce_legacy_auth', true);
        $request->session()->put('usuario_id', 0);

        $result = $middleware->handle($request, fn (): Response => new Response(self::DUMMY_RESPONSE));

        self::assertInstanceOf(RedirectResponse::class, $result);
        /** @var RedirectResponse $result */
        self::assertSame(route('migration.login'), $result->getTargetUrl());
    }

    public function testAllowsRequestWhenUserIsAdmin(): void
    {
        $middleware = new RequireLegacyAdmin();
        $request = Request::create('/admin', 'GET');
        $request->setLaravelSession($this->app['session']->driver());
        $request->session()->put('_enforce_legacy_auth', true);
        $request->session()->put('usuario_id', 42);
        $request->session()->put('is_admin', true);

        $result = $middleware->handle($request, fn (): Response => new Response(self::DUMMY_RESPONSE));

        self::assertInstanceOf(Response::class, $result);
        self::assertSame(self::DUMMY_RESPONSE, $result->getContent());
    }

    public function testAllowsRequestWhenUserIsAdminAsStringOne(): void
    {
        $middleware = new RequireLegacyAdmin();
        $request = Request::create('/admin', 'GET');
        $request->setLaravelSession($this->app['session']->driver());
        $request->session()->put('_enforce_legacy_auth', true);
        $request->session()->put('usuario_id', 7);
        $request->session()->put('is_admin', '1');

        $result = $middleware->handle($request, fn (): Response => new Response(self::DUMMY_RESPONSE));

        self::assertInstanceOf(Response::class, $result);
        self::assertSame(self::DUMMY_RESPONSE, $result->getContent());
    }

    public function testRedirectsNonAdminToDashboardWithError(): void
    {
        $middleware = new RequireLegacyAdmin();
        $request = Request::create('/admin', 'GET');
        $request->setLaravelSession($this->app['session']->driver());
        $request->session()->put('_enforce_legacy_auth', true);
        $request->session()->put('usuario_id', 42);
        $request->session()->put('is_admin', false);

        $result = $middleware->handle($request, fn (): Response => new Response(self::DUMMY_RESPONSE));

        self::assertInstanceOf(RedirectResponse::class, $result);
        /** @var RedirectResponse $result */
        self::assertSame(route('migration.dashboard'), $result->getTargetUrl());
        self::assertSame(
            'Seu perfil não tem permissão para executar esta ação.',
            $request->session()->get('status'),
        );
        self::assertSame('error', $request->session()->get('status_type'));
    }

    public function testRedirectsNonAdminWhenIsAdminNotSet(): void
    {
        $middleware = new RequireLegacyAdmin();
        $request = Request::create('/admin', 'GET');
        $request->setLaravelSession($this->app['session']->driver());
        $request->session()->put('_enforce_legacy_auth', true);
        $request->session()->put('usuario_id', 42);

        $result = $middleware->handle($request, fn (): Response => new Response(self::DUMMY_RESPONSE));

        self::assertInstanceOf(RedirectResponse::class, $result);
        /** @var RedirectResponse $result */
        self::assertSame(route('migration.dashboard'), $result->getTargetUrl());
        self::assertSame(
            'Seu perfil não tem permissão para executar esta ação.',
            $request->session()->get('status'),
        );
        self::assertSame('error', $request->session()->get('status_type'));
    }

    public function testRedirectsToLoginWhenUsuarioIdIsNegative(): void
    {
        $middleware = new RequireLegacyAdmin();
        $request = Request::create('/admin', 'GET');
        $request->setLaravelSession($this->app['session']->driver());
        $request->session()->put('_enforce_legacy_auth', true);
        $request->session()->put('usuario_id', -1);

        $result = $middleware->handle($request, fn (): Response => new Response(self::DUMMY_RESPONSE));

        self::assertInstanceOf(RedirectResponse::class, $result);
        /** @var RedirectResponse $result */
        self::assertSame(route('migration.login'), $result->getTargetUrl());
    }
}
