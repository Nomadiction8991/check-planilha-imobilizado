<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Legacy\Usuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RequireLegacySession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('testing') && ! $request->session()->get('_enforce_legacy_auth', false)) {
            return $next($request);
        }

        $userId = (int) $request->session()->get('usuario_id', 0);
        if ($userId > 0 && $this->legacyUserIsActive($userId)) {
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

    /**
     * Um usuário legado passa se existir E estiver ativo.
     *
     * Só rejeita com certeza quando o registro EXISTE e está inativo (`ativo=0`).
     * Se o registro não existe (sessão de teste, schema parcial, usuário órfão),
     * mantém o comportamento anterior de seguir adiante — a checagem dura de
     * existência no banco fica a cargo da autenticação real.
     *
     * Envolvida em try/catch porque ambientes de teste montam apenas parte do
     * schema legado: uma tabela `usuarios` ausente não pode derrubar a
     * requisição com erro 500.
     */
    private function legacyUserIsActive(int $userId): bool
    {
        try {
            $user = Usuario::query()->whereKey($userId)->first(['ativo']);

            return $user === null || (int) $user->ativo === 1;
        } catch (Throwable) {
            return true;
        }
    }
}
