<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyPasswordRecoveryServiceInterface;
use App\Http\Requests\LegacyPasswordResetRequest;
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

        $redirectTarget = (string) $request->session()->pull('redirect_after_login', '');
        if ($redirectTarget !== '' && str_starts_with($redirectTarget, '/')) {
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

        $request->session()->put('comum_id', (int) $validated['comum_id']);

        $redirectTo = (string) ($validated['redirect_to'] ?? '');

        if ($redirectTo !== '' && str_starts_with($redirectTo, '/')) {
            return redirect($redirectTo)
                ->with('status', 'Igreja ativa atualizada.')
                ->with('status_type', 'success');
        }

        return redirect()
            ->back()
            ->with('status', 'Igreja ativa atualizada.')
            ->with('status_type', 'success');
    }
}
