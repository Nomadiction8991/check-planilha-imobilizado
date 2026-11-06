<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Sync\ConflictResolver;
use App\Sync\ConflitoNaoAplicavelException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Tela de conflitos da sincronização: a pessoa escolhe qual valor fica. Só administradores.
 */
final class ConflitosController extends Controller
{
    public function __construct(
        private readonly ConflictResolver $conflitos,
    ) {
    }

    public function index(): View
    {
        return view('conflitos.index', ['conflitos' => $this->conflitos->pendentes()]);
    }

    public function manterNotebook(int $conflito): RedirectResponse
    {
        try {
            abort_unless($this->conflitos->manterDoNotebook($conflito), 404);
        } catch (ConflitoNaoAplicavelException $erro) {
            return redirect()
                ->route('migration.conflitos.index')
                ->withErrors(['conflito' => $erro->getMessage()]);
        }

        return redirect()
            ->route('migration.conflitos.index')
            ->with('status', 'Valor do notebook mantido. A mudança foi aplicada no servidor.');
    }

    public function manterServidor(int $conflito): RedirectResponse
    {
        abort_unless($this->conflitos->manterDoServidor($conflito), 404);

        return redirect()
            ->route('migration.conflitos.index')
            ->with('status', 'Valor do servidor mantido. O notebook vai receber esse valor.');
    }
}
