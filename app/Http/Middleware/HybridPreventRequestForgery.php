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
        // SIMPLE TEST - always log
        $this->debugLog($request, 'tokensMatch called');

        // --- Inspect session state ---
        $session = $request->session();
        $sessionToken = $session->token();
        $sessionHasToken = $session->has('_token');
        $sessionDriver = config('session.driver');

        $this->debugLog($request, 'Session state', [
            'driver' => $sessionDriver,
            'has_token_in_store' => $sessionHasToken,
            'session_id_prefix' => mb_substr($session->getId() ?? '', 0, 12) . '…',
        ]);

        // --- Parent check (standard Laravel CSRF) ---
        $parentToken = $this->getTokenFromRequest($request);
        $parentMatches = is_string($sessionToken)
            && is_string($parentToken)
            && hash_equals($sessionToken, $parentToken);

        if (! $parentMatches) {
            $this->debugLog($request, 'Parent tokensMatch FAILED', [
                'session_token_prefix' => is_string($sessionToken) ? mb_substr($sessionToken, 0, 12) . '…' : 'NOT_A_STRING',
                'request_token_prefix' => is_string($parentToken) ? mb_substr($parentToken, 0, 12) . '…' : 'NOT_A_STRING',
                'session_token_type' => gettype($sessionToken),
                'request_token_type' => gettype($parentToken),
                'session_token_len' => is_string($sessionToken) ? strlen($sessionToken) : 0,
                'request_token_len' => is_string($parentToken) ? strlen($parentToken) : 0,
            ]);
        }

        if ($parentMatches) {
            return true;
        }

        // --- Legacy native CSRF check ---
        $legacyToken = $request->attributes->get('_legacy_native_csrf_token');
        $requestToken = $request->input('_csrf_token') ?: $request->header('X-CSRF-TOKEN');

        $this->debugLog($request, 'Legacy check attempt', [
            'has_legacy_token' => isset($legacyToken),
            'legacy_token_prefix' => is_string($legacyToken) && $legacyToken !== '' ? mb_substr($legacyToken, 0, 12) . '…' : gettype($legacyToken),
            'request_token_prefix' => is_string($requestToken) && $requestToken !== '' ? mb_substr($requestToken, 0, 12) . '…' : gettype($requestToken),
        ]);

        $result = is_string($legacyToken)
            && $legacyToken !== ''
            && is_string($requestToken)
            && $requestToken !== ''
            && hash_equals($legacyToken, $requestToken);

        if (! $result) {
            $this->debugLog($request, 'Legacy check FAILED', [
                'legacy_is_string' => is_string($legacyToken),
                'legacy_not_empty' => is_string($legacyToken) && $legacyToken !== '',
                'request_is_string' => is_string($requestToken),
                'request_not_empty' => is_string($requestToken) && $requestToken !== '',
            ]);
        }

        return $result;
    }

    /**
     * Debug log helper — avoids PHP 8.5 closure variable capture bug.
     */
    private function debugLog($request, string $msg, array $ctx = []): void
    {
        $prefix = '[CSRF_DEBUG]';
        $url = $request->method() . ' ' . $request->path();
        $ctxStr = empty($ctx) ? '' : ' | ' . json_encode($ctx, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        error_log("{$prefix} {$url} — {$msg}{$ctxStr}", 3, '/var/www/checkplanilha/storage/logs/debug.log');
    }
}
