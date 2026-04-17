<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\LegacyPermissionServiceInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireLegacyPermission
{
    public function __construct(
        private readonly LegacyPermissionServiceInterface $permissions,
    ) {
    }

    public function handle(Request $request, Closure $next, string $ability): Response
    {
        if (app()->environment('testing') && !$request->session()->get('_enforce_legacy_auth', false)) {
            return $next($request);
        }

        if ((int) $request->session()->get('usuario_id', 0) <= 0) {
            return redirect()
                ->route('migration.login');
        }

        if ($this->permissions->can($ability)) {
            return $next($request);
        }

        return redirect()
            ->route('migration.dashboard')
            ->with('status', 'Seu perfil não tem permissão para executar esta ação.')
            ->with('status_type', 'error');
    }
}
