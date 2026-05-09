<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Legacy\Comum;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PublicAccessController extends Controller
{
    public function create(Request $request): View
    {
        $request->session()->forget([
            'public_acesso',
            'public_planilha_id',
            'public_comum_id',
            'public_comum',
        ]);

        return view('public-access.create', [
            'churches' => Comum::query()
                ->select(['id', 'descricao'])
                ->orderBy('descricao')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'comum_id' => ['required', 'integer', 'min:1'],
        ]);

        $church = Comum::query()
            ->select(['id', 'descricao'])
            ->find((int) $validated['comum_id']);

        if ($church === null) {
            return back()
                ->withInput()
                ->with('status', 'Comum não encontrada ou inativa.')
                ->with('status_type', 'error');
        }

        $request->session()->put([
            'public_acesso' => true,
            'public_planilha_id' => (int) $church->id,
            'public_comum_id' => (int) $church->id,
            'public_comum' => (string) $church->descricao,
        ]);

        return redirect()
            ->to('/churches/public?contexto=planilha&id=' . urlencode((string) $church->id) . '&comum_id=' . urlencode((string) $church->id) . '&publico=1');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget([
            'public_acesso',
            'public_planilha_id',
            'public_comum_id',
            'public_comum',
        ]);

        return redirect()->route('migration.login');
    }
}
