<?php
/**
 * Teste de segurança: garante que o CSRF híbrido está ativo no grupo web
 * e que as rotas AJAX isentas continuam acessíveis sem token.
 *
 * Cobre a regressão em que o registro do middleware usava replace() global
 * (que não afeta o grupo 'web') em vez de replaceInGroup().
 */
namespace Tests\Feature;

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

#[TestDox('Registro do middleware CSRF híbrido no grupo web')]
final class HybridCsrfMiddlewareRegistrationTest extends TestCase
{
    /**
     * Middleware efetivamente registrado no grupo web (após bootstrap completo).
     *
     * @return array<int, class-string|string>
     */
    private function webGroupMiddleware(): array
    {
        // Garante que a aplicação passou pelo bootstrap (withMiddleware aplicado)
        $kernel = $this->app->make(HttpKernel::class);
        $kernel->handle($request = \Illuminate\Http\Request::create('/up', 'GET'));

        $router = $this->app->make('router');

        return $router->getMiddlewareGroups()['web'] ?? [];
    }

    public function test_web_group_uses_hybrid_csrf_middleware(): void
    {
        $webGroup = $this->webGroupMiddleware();

        $this->assertContains(
            \App\Http\Middleware\HybridPreventRequestForgery::class,
            $webGroup,
            "O grupo 'web' deve usar o middleware CSRF híbrido com rotas isentas.",
        );

        $this->assertNotContains(
            \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
            $webGroup,
            "O grupo 'web' não deve manter o CSRF padrão sem rotas isentas.",
        );
    }

    public function test_exempt_paths_are_declared_on_active_middleware(): void
    {
        $middleware = $this->app->make(\App\Http\Middleware\HybridPreventRequestForgery::class);
        $exempt = $middleware->getExcludedPaths();

        $this->assertContains('/spreadsheets/preview/*/actions', $exempt);
        $this->assertContains('/spreadsheets/process/*/start', $exempt);
    }

    public function test_exempt_route_is_not_blocked_by_csrf_in_production_env(): void
    {
        // Simula ambiente de produção para forçar a verificação de token
        $this->app->instance('env', 'production');

        $response = $this->postJson('/spreadsheets/preview/999/actions', ['acoes' => []]);

        $this->assertNotSame(
            419,
            $response->getStatusCode(),
            'Rota isenta não pode ser bloqueada pelo CSRF mesmo fora do ambiente de teste.',
        );
    }
}
