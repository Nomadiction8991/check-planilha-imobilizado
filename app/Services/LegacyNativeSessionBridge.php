<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Legacy\Administracao;
use App\Models\Legacy\Usuario;
use Illuminate\Http\Request;
use Illuminate\Session\Store;

class LegacyNativeSessionBridge
{
    private const array SYNC_KEYS = [
        'usuario_id',
        'usuario_nome',
        'usuario_email',
        'comum_id',
        'administracao_id',
        'administracoes_permitidas',
        'is_admin',
        'legacy_permissions',
    ];

    private const string LEGACY_CSRF_KEY = '_csrf_token';

    public function import(Request $request): void
    {
        if ($this->shouldSkipInTesting($request)) {
            return;
        }

        $nativeSession = $this->readNativeSession($request);
        $request->attributes->set('_legacy_native_session', $nativeSession);
        $request->attributes->set('_legacy_native_cookie_present', $this->hasNativeCookie($request));
        $request->attributes->set(
            '_legacy_native_csrf_token',
            is_string($nativeSession[self::LEGACY_CSRF_KEY] ?? null) ? $nativeSession[self::LEGACY_CSRF_KEY] : null,
        );

        if ($this->hasAuthenticatedUser($nativeSession)) {
            $request->session()->put($this->normalizeAuthenticatedPayload($nativeSession));
            return;
        }

        if ($this->hasNativeCookie($request)) {
            $request->session()->forget(self::SYNC_KEYS);
        }
    }

    public function export(Request $request): void
    {
        if ($this->shouldSkipInTesting($request)) {
            return;
        }

        $initialNativeSession = (array) $request->attributes->get('_legacy_native_session', []);
        $hadNativeCookie = (bool) $request->attributes->get('_legacy_native_cookie_present', false);
        $laravelSession = $this->extractLaravelSession($request->session());

        if (!$this->hasAuthenticatedUser($laravelSession)) {
            if ($this->hasAuthenticatedUser($initialNativeSession) || $hadNativeCookie) {
                $this->destroyNativeSession($request);
            }

            return;
        }

        $this->startNativeSession($request);

        $initialUserId = (int) ($initialNativeSession['usuario_id'] ?? 0);
        $currentUserId = (int) ($laravelSession['usuario_id'] ?? 0);

        if ($initialUserId <= 0 || $initialUserId !== $currentUserId) {
            session_regenerate_id(true);
        }

        foreach (self::SYNC_KEYS as $key) {
            if (array_key_exists($key, $laravelSession)) {
                $_SESSION[$key] = $laravelSession[$key];
                continue;
            }

            unset($_SESSION[$key]);
        }

        session_write_close();
    }

    /**
     * @return array<string, mixed>
     */
    private function readNativeSession(Request $request): array
    {
        $this->startNativeSession($request);

        $payload = [];
        foreach (self::SYNC_KEYS as $key) {
            if (array_key_exists($key, $_SESSION)) {
                $payload[$key] = $_SESSION[$key];
            }
        }

        if (array_key_exists(self::LEGACY_CSRF_KEY, $_SESSION)) {
            $payload[self::LEGACY_CSRF_KEY] = $_SESSION[self::LEGACY_CSRF_KEY];
        }

        session_write_close();

        return $payload;
    }

    private function destroyNativeSession(Request $request): void
    {
        $this->startNativeSession($request);
        $_SESSION = [];

        session_destroy();

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'] ?: '/',
                'domain' => $params['domain'] ?: '',
                'secure' => (bool) ($params['secure'] ?? false),
                'httponly' => (bool) ($params['httponly'] ?? true),
                'samesite' => (string) ($params['samesite'] ?? 'Lax'),
            ]);
        }

        session_write_close();
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function normalizeAuthenticatedPayload(array $payload): array
    {
        $userId = (int) ($payload['usuario_id'] ?? 0);
        if ($userId <= 0) {
            return $payload;
        }

        if (app()->environment('testing')) {
            return $this->normalizeAuthenticatedPayloadWithoutDatabase($payload);
        }

        try {
            /** @var Usuario|null $user */
            $user = Usuario::query()->find($userId);
        } catch (\Throwable $throwable) {
            return $this->normalizeAuthenticatedPayloadWithoutDatabase($payload);
        }

        if ($user === null) {
            return $payload;
        }

        $payload['is_admin'] = $user->isAdministrator();

        if ($user->isAdministrator()) {
            $payload['legacy_permissions'] = array_fill_keys(
                array_keys((array) config('legacy.permissions.defaults', [])),
                true
            );

            try {
                $payload['administracoes_permitidas'] = $this->allAdministrationIds();
            } catch (\Throwable $throwable) {
                // Mantém o escopo já presente na sessão nativa quando o banco não estiver disponível.
            }
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function normalizeAuthenticatedPayloadWithoutDatabase(array $payload): array
    {
        $user = new Usuario();
        $user->forceFill([
            'id' => (int) ($payload['usuario_id'] ?? 0),
            'email' => (string) ($payload['usuario_email'] ?? ''),
            'tipo' => (string) ($payload['tipo'] ?? ''),
        ]);

        $payload['is_admin'] = $user->isAdministrator() || (bool) ($payload['is_admin'] ?? false);

        if ($payload['is_admin']) {
            $payload['legacy_permissions'] = array_fill_keys(
                array_keys((array) config('legacy.permissions.defaults', [])),
                true
            );

            if (array_key_exists('administracoes_permitidas', $payload) && is_array($payload['administracoes_permitidas'])) {
                $payload['administracoes_permitidas'] = array_values(array_filter(array_map(
                    static fn (mixed $value): int => (int) $value,
                    $payload['administracoes_permitidas'],
                ), static fn (int $value): bool => $value > 0));
            }
        }

        return $payload;
    }

    private function startNativeSession(Request $request): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $isSecure = $request->isSecure()
            || $request->headers->get('X-Forwarded-Proto') === 'https';

        session_start([
            'cookie_httponly' => true,
            'cookie_secure' => $isSecure,
            'cookie_samesite' => 'Lax',
        ]);
    }

    private function hasNativeCookie(Request $request): bool
    {
        return $request->cookies->has(session_name());
    }

    /**
     * @return array<string, mixed>
     */
    private function extractLaravelSession(Store $session): array
    {
        $payload = [];

        foreach (self::SYNC_KEYS as $key) {
            if ($session->has($key)) {
                $payload[$key] = $session->get($key);
            }
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function hasAuthenticatedUser(array $payload): bool
    {
        return (int) ($payload['usuario_id'] ?? 0) > 0;
    }

    /**
     * @return array<int, int>
     */
    private function allAdministrationIds(): array
    {
        return Administracao::query()
            ->orderBy('descricao')
            ->pluck('id')
            ->map(static fn (mixed $value): int => (int) $value)
            ->filter(static fn (int $value): bool => $value > 0)
            ->values()
            ->all();
    }

    private function shouldSkipInTesting(Request $request): bool
    {
        if (!app()->environment('testing')) {
            return false;
        }

        return !$request->session()->get('_enforce_native_bridge', false)
            && !$this->hasNativeCookie($request);
    }
}
