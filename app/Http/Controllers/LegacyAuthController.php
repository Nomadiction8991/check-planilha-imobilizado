<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyPasswordRecoveryServiceInterface;
use App\Http\Requests\LegacyPasswordResetRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class LegacyAuthController extends Controller
{
    public function __construct(
        private readonly LegacyAuthSessionServiceInterface $auth,
    ) {
    }

    public function showLogin(): View|RedirectResponse
    {
        if ($this->auth->isAuthenticated()) {
            return redirect()->route('migration.dashboard');
        }

        return view('auth.login');
    }

    public function showForgotPassword(): View|RedirectResponse
    {
        if ($this->auth->isAuthenticated()) {
            return redirect()->route('migration.dashboard');
        }

        return view('auth.forgot-password');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'senha' => ['required', 'string'],
        ]);

        try {
            $authenticatedUser = $this->auth->attempt($validated['email'], $validated['senha']);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('migration.login')
                ->withInput($request->only('email'))
                ->with('status', $exception->getMessage())
                ->with('status_type', 'error');
        }

        $request->session()->put([
            'usuario_id' => (int) $authenticatedUser['id'],
            'usuario_nome' => (string) $authenticatedUser['nome'],
            'usuario_email' => (string) $authenticatedUser['email'],
            'comum_id' => $authenticatedUser['comum_id'] ?? null,
            'administracao_id' => $authenticatedUser['administracao_id'] ?? null,
            'administracoes_permitidas' => $authenticatedUser['administracoes_permitidas'] ?? [],
            'is_admin' => (bool) $authenticatedUser['is_admin'],
            'legacy_permissions' => $authenticatedUser['legacy_permissions'] ?? $request->session()->get('legacy_permissions', []),
        ]);

        $redirectTarget = $this->resolveInternalRedirectTarget((string) $request->session()->pull('redirect_after_login', ''));
        if ($redirectTarget !== null) {
            return redirect($redirectTarget);
        }

        return redirect()
            ->route('migration.dashboard')
            ->with('status', 'Login realizado com sucesso.')
            ->with('status_type', 'success');
    }

    public function sendForgotPassword(
        LegacyPasswordResetRequest $request,
        LegacyPasswordRecoveryServiceInterface $passwordRecovery
    ): RedirectResponse {
        $validated = $request->validated();

        try {
            $passwordRecovery->recover(
                (string) $validated['cpf'],
                (string) $validated['telefone'],
                (string) $validated['email'],
            );
        } catch (RuntimeException $exception) {
            return back()
                ->withInput($request->only('cpf', 'telefone', 'email'))
                ->with('status', $exception->getMessage())
                ->with('status_type', 'error');
        }

        return redirect()
            ->route('migration.login')
            ->with('status', 'Nova senha enviada para o e-mail cadastrado.')
            ->with('status_type', 'success');
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->auth->logout();
        $request->session()->forget([
            'usuario_id',
            'usuario_nome',
            'usuario_email',
            'comum_id',
            'administracao_id',
            'administracoes_permitidas',
            'is_admin',
            'legacy_permissions',
        ]);

        return redirect()
            ->route('migration.login')
            ->with('status', 'Sessão encerrada.')
            ->with('status_type', 'success');
    }

    public function switchChurch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'comum_id' => ['required', 'integer', 'min:1'],
            'redirect_to' => ['nullable', 'string'],
        ]);

        try {
            $this->auth->switchChurch((int) $validated['comum_id']);
        } catch (RuntimeException $exception) {
            return back()
                ->with('status', $exception->getMessage())
                ->with('status_type', 'error');
        }

        $redirectTo = $this->resolveInternalRedirectTarget((string) ($validated['redirect_to'] ?? ''));

        if ($redirectTo !== null) {
            return redirect($redirectTo)
                ->with('status', 'Igreja ativa atualizada.')
                ->with('status_type', 'success');
        }

        return redirect()
            ->back()
            ->with('status', 'Igreja ativa atualizada.')
            ->with('status_type', 'success');
    }

    private function resolveInternalRedirectTarget(string $redirectTarget): ?string
    {
        $candidate = trim($redirectTarget);
        if ($candidate === '') {
            return null;
        }

        if (str_starts_with($candidate, '/')) {
            return str_starts_with($candidate, '//') ? null : $candidate;
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        if ($appUrl === '') {
            return null;
        }

        $candidateParts = parse_url($candidate);
        if ($candidateParts === false || !isset($candidateParts['path'])) {
            return null;
        }

        $candidateHost = $candidateParts['host'] ?? null;
        if (!is_string($candidateHost) || $candidateHost === '') {
            return null;
        }

        $appHost = parse_url($appUrl, PHP_URL_HOST);
        if (!is_string($appHost) || $appHost === '' || !hash_equals($appHost, $candidateHost)) {
            return null;
        }

        $path = (string) $candidateParts['path'];
        if ($path === '' || !str_starts_with($path, '/')) {
            return null;
        }

        $query = isset($candidateParts['query']) && $candidateParts['query'] !== ''
            ? '?' . $candidateParts['query']
            : '';

        $fragment = isset($candidateParts['fragment']) && $candidateParts['fragment'] !== ''
            ? '#' . $candidateParts['fragment']
            : '';

        return $path . $query . $fragment;
    }

    public function storeFilterPin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scope' => ['required', 'string', 'max:255'],
            'index' => ['required', 'integer', 'min:0'],
            'pinned' => ['required', 'boolean'],
        ]);

        try {
            $this->auth->storeFilterPinState(
                (string) $validated['scope'],
                (int) $validated['index'],
                (bool) $validated['pinned'],
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
        ]);
    }
}
