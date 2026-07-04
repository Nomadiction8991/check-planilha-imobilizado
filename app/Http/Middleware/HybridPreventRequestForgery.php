<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

class HybridPreventRequestForgery extends PreventRequestForgery
{
    /**
     * Rotas AJAX POST que usam CSRF via header X-CSRF-TOKEN.
     * Mantidas fora da verificação padrão por compatibilidade com sessão híbrida.
     *
     * @var array<int, string>
     */
    protected $except = [
        '/spreadsheets/preview/*/actions',
        '/spreadsheets/preview/*/bulk-action',
        '/spreadsheets/process/*/start',
    ];

    protected function tokensMatch($request)
    {
        if (parent::tokensMatch($request)) {
            return true;
        }

        $legacyToken = $request->attributes->get('_legacy_native_csrf_token');
        $requestToken = $request->input('_csrf_token') ?: $request->header('X-CSRF-TOKEN');

        return is_string($legacyToken)
            && $legacyToken !== ''
            && is_string($requestToken)
            && $requestToken !== ''
            && hash_equals($legacyToken, $requestToken);
    }
}
