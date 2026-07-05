<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\HybridPreventRequestForgery;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Tests for HybridPreventRequestForgery middleware.
 *
 * Focus: regression coverage for the $request undefined variable fix,
 * plus all three CSRF verification paths (standard, legacy native, legacy header).
 *
 * The parent PreventRequestForgery::runningUnitTests() bypasses CSRF in
 * APP_ENV=testing, so we test tokensMatch() directly via closure binding
 * and also exercise handle() in production-mode to confirm no exceptions
 * on the full middleware execution path.
 */
final class HybridPreventRequestForgeryTest extends TestCase
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

    // ── helpers ──────────────────────────────────────────────────────────

    private function createMiddleware(): HybridPreventRequestForgery
    {
        return new HybridPreventRequestForgery(
            $this->app,
            $this->app['encrypter'],
        );
    }

    private function makePostRequest(string $path = '/login'): Request
    {
        $request = Request::create($path, 'POST');
        $request->setLaravelSession($this->app['session']->driver());

        return $request;
    }

    /**
     * Bind-and-call the protected tokensMatch() method.
     */
    private function callTokensMatch(HybridPreventRequestForgery $middleware, Request $request): mixed
    {
        $bound = \Closure::bind(
            fn (Request $r): mixed => $this->tokensMatch($r),
            $middleware,
            HybridPreventRequestForgery::class,
        );

        return $bound($request);
    }

    /**
     * Bind-and-call the protected handle() method so we can test it
     * without going through the full HTTP kernel.
     */
    private function callHandle(HybridPreventRequestForgery $middleware, Request $request): mixed
    {
        $bound = \Closure::bind(
            fn (Request $r): mixed => $this->handle($r, fn ($req) => new Response('OK', 200)),
            $middleware,
            HybridPreventRequestForgery::class,
        );

        return $bound($request);
    }

    /**
     * Temporarily set app env to 'production' so CSRF middleware runs fully.
     */
    private function enableFullCsrf(): void
    {
        $this->app->instance('env', 'production');
    }

    // ── tokensMatch() regression tests ───────────────────────────────────

    public function test_tokens_match_does_not_throw_when_no_session_is_started(): void
    {
        $middleware = $this->createMiddleware();
        $request = $this->makePostRequest();

        // Session driver exists but no session started —
        // should NOT throw "Undefined variable $request"
        $result = $this->callTokensMatch($middleware, $request);

        $this->assertFalse($result);
    }

    public function test_tokens_match_returns_false_without_any_token(): void
    {
        $middleware = $this->createMiddleware();
        $request = $this->makePostRequest();
        $request->session()->start();

        $result = $this->callTokensMatch($middleware, $request);

        // Session has a token, but request has none — parent check fails,
        // and no legacy token is set, so the final result is false
        $this->assertFalse($result);
    }

    public function test_tokens_match_returns_false_with_mismatched_session_token(): void
    {
        $middleware = $this->createMiddleware();
        $request = $this->makePostRequest();
        $request->session()->start();
        $request->request->set('_token', 'some-random-token-that-does-not-match');

        $result = $this->callTokensMatch($middleware, $request);

        $this->assertFalse($result);
    }

    public function test_tokens_match_returns_true_with_matching_session_token(): void
    {
        $middleware = $this->createMiddleware();
        $request = $this->makePostRequest();
        $request->session()->start();
        $token = $request->session()->token();
        $request->request->set('_token', $token);

        $result = $this->callTokensMatch($middleware, $request);

        $this->assertTrue($result);
    }

    public function test_tokens_match_returns_true_with_matching_header_token(): void
    {
        $middleware = $this->createMiddleware();
        $request = $this->makePostRequest();
        $request->session()->start();
        $token = $request->session()->token();
        $request->headers->set('X-CSRF-TOKEN', $token);

        $result = $this->callTokensMatch($middleware, $request);

        $this->assertTrue($result);
    }

    // ── Legacy native CSRF path ──────────────────────────────────────────

    public function test_tokens_match_returns_true_with_legacy_native_token_via_input(): void
    {
        $middleware = $this->createMiddleware();
        $request = $this->makePostRequest();
        $request->session()->start();

        $legacyToken = 'legacy-native-csrf-abc123';
        $request->attributes->set('_legacy_native_csrf_token', $legacyToken);
        $request->request->set('_csrf_token', $legacyToken);

        $result = $this->callTokensMatch($middleware, $request);

        $this->assertTrue($result);
    }

    public function test_tokens_match_returns_true_with_legacy_native_token_via_header(): void
    {
        $middleware = $this->createMiddleware();
        $request = $this->makePostRequest();
        $request->session()->start();

        $legacyToken = 'legacy-native-csrf-xyz789';
        $request->attributes->set('_legacy_native_csrf_token', $legacyToken);
        $request->headers->set('X-CSRF-TOKEN', $legacyToken);

        $result = $this->callTokensMatch($middleware, $request);

        $this->assertTrue($result);
    }

    public function test_tokens_match_returns_false_when_legacy_token_is_empty(): void
    {
        $middleware = $this->createMiddleware();
        $request = $this->makePostRequest();
        $request->session()->start();

        // Empty string in _legacy_native_csrf_token — should not match
        $request->attributes->set('_legacy_native_csrf_token', '');
        $request->request->set('_csrf_token', 'some-token');

        $result = $this->callTokensMatch($middleware, $request);

        $this->assertFalse($result);
    }

    public function test_tokens_match_returns_false_when_legacy_token_is_not_set(): void
    {
        $middleware = $this->createMiddleware();
        $request = $this->makePostRequest();
        $request->session()->start();

        // Legacy token attribute not set at all — should fall through to false
        $request->request->set('_csrf_token', 'some-token');

        $result = $this->callTokensMatch($middleware, $request);

        $this->assertFalse($result);
    }

    public function test_tokens_match_legacy_prefers_input_over_header(): void
    {
        $middleware = $this->createMiddleware();
        $request = $this->makePostRequest();
        $request->session()->start();

        $legacyToken = 'legacy-token-42';
        $request->attributes->set('_legacy_native_csrf_token', $legacyToken);

        // Both input and header present — input should be used (first check)
        $request->request->set('_csrf_token', $legacyToken);
        $request->headers->set('X-CSRF-TOKEN', 'wrong-token');

        $result = $this->callTokensMatch($middleware, $request);

        $this->assertTrue($result);
    }

    // ── Edge cases ───────────────────────────────────────────────────────

    public function test_tokens_match_handles_get_request_safely(): void
    {
        $middleware = $this->createMiddleware();
        $request = Request::create('/some-page', 'GET');
        $request->setLaravelSession($this->app['session']->driver());

        // GET requests are never checked by handle(), but tokensMatch()
        // shouldn't blow up if called directly
        $this->expectNotToPerformAssertions();
        $this->callTokensMatch($middleware, $request);
    }

    public function test_tokens_match_throws_runtime_exception_when_no_session_store(): void
    {
        $middleware = $this->createMiddleware();
        $request = Request::create('/login', 'POST');

        // No session attached — $request->session() throws RuntimeException.
        // This is expected Laravel behaviour when the middleware pipeline
        // hasn't set up a session yet.
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Session store not set on request.');

        $this->callTokensMatch($middleware, $request);
    }

    // ── handle() integration tests ───────────────────────────────────────

    public function test_handle_passes_post_without_csrf_when_running_unit_tests(): void
    {
        $middleware = $this->createMiddleware();
        $request = $this->makePostRequest();

        // runningUnitTests() returns true in test env, so CSRF is bypassed
        $response = $this->callHandle($middleware, $request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getContent());
    }

    public function test_handle_passes_excluded_path_without_csrf(): void
    {
        $this->enableFullCsrf();
        $middleware = $this->createMiddleware();

        // This path matches the $except array: /spreadsheets/preview/*/actions
        $request = $this->makePostRequest('/spreadsheets/preview/42/actions');

        $response = $this->callHandle($middleware, $request);

        // inExceptArray should return true, so the request passes
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getContent());
    }

    public function test_handle_rejects_post_without_csrf_in_production(): void
    {
        $this->enableFullCsrf();
        $middleware = $this->createMiddleware();
        $request = $this->makePostRequest();

        $this->expectException(\Illuminate\Session\TokenMismatchException::class);
        $this->expectExceptionMessage('CSRF token mismatch.');

        $this->callHandle($middleware, $request);
    }

    public function test_handle_passes_post_with_valid_csrf_in_production(): void
    {
        $this->enableFullCsrf();
        $middleware = $this->createMiddleware();
        $request = $this->makePostRequest();
        $request->session()->start();
        $token = $request->session()->token();
        $request->request->set('_token', $token);

        $response = $this->callHandle($middleware, $request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getContent());
    }
}
