<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireLegacyAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('testing') && !$request->session()->get('_enforce_legacy_auth', false)) {
            return $next($request);
        }

        if ((int) $request->session()->get('usuario_id', 0) <= 0) {
            return redirect()
                ->route('migration.login');
        }

        if ((bool) $request->session()->get('is_admin', false)) {
            return $next($request);
        }

        return redirect()
            ->route('migration.dashboard')
            ->with('status', 'Seu perfil não tem permissão para executar esta ação.')
            ->with('status_type', 'error');
    }
}
