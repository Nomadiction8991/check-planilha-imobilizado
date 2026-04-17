<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireLegacySession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('testing') && !$request->session()->get('_enforce_legacy_auth', false)) {
            return $next($request);
        }

        if ((int) $request->session()->get('usuario_id', 0) > 0) {
            return $next($request);
        }

        if ($request->session()->get('public_acesso', false) && (
            (int) $request->session()->get('public_comum_id', 0) > 0
            || (int) $request->session()->get('public_planilha_id', 0) > 0
        )) {
            return $next($request);
        }

        $request->session()->put('redirect_after_login', $request->getRequestUri());

        return redirect()
            ->route('migration.login');
    }
}
