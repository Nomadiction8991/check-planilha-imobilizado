<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\LegacyChurchBrowserServiceInterface;
use App\Contracts\LegacyChurchManagementServiceInterface;
use App\DTO\ChurchFilters;
use App\Models\Legacy\Administracao;
use App\Http\Requests\UpdateLegacyChurchRequest;
use App\Models\Legacy\Comum;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class LegacyChurchController extends Controller
{
    public function __construct(
        private readonly LegacyChurchBrowserServiceInterface $churchs,
        private readonly LegacyChurchManagementServiceInterface $churchManager,
    ) {
    }

    public function index(Request $request): View
    {
        $filters = ChurchFilters::fromRequest($request);
        $churches = $this->churchs->paginate($filters)->appends($filters->toQuery());

        return view('churches.index', [
            'filters' => $filters,
            'churches' => $churches,
            'totalAll' => $this->churchs->countAll(),
        ]);
    }

    public function edit(Comum $church): View
    {
        return view('churches.edit', [
            'church' => $church,
            'administrations' => Administracao::query()->orderBy('descricao')->get(['id', 'descricao']),
            'states' => (array) config('brazil.states', []),
        ]);
    }

    public function update(UpdateLegacyChurchRequest $request, Comum $church): RedirectResponse
    {
        try {
            $this->churchManager->update($church, $request->toDto());
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('migration.churches.edit', ['church' => $church->id])
                ->withInput()
                ->with('status', $exception->getMessage())
                ->with('status_type', 'error');
        }

        return redirect()
            ->route('migration.churches.index')
            ->with('status', 'Igreja atualizada com sucesso.')
            ->with('status_type', 'success');
    }

    public function productsCount(Request $request): JsonResponse
    {
        $churchId = max(0, (int) $request->query('comum_id', 0));

        if ($churchId <= 0) {
            return response()->json([
                'count' => 0,
                'error' => 'ID inválido',
            ], 400);
        }

        try {
            $count = $this->churchManager->countProducts($churchId);
        } catch (\Throwable $exception) {
            return response()->json([
                'count' => 0,
                'error' => $exception->getMessage(),
            ], 500);
        }

        return response()->json(['count' => $count]);
    }

    public function deleteProducts(Request $request): RedirectResponse
    {
        $churchId = max(0, (int) $request->input('comum_id', 0));

        if ($churchId <= 0) {
            return redirect()
                ->route('migration.churches.index')
                ->with('status', 'ID de igreja inválido.')
                ->with('status_type', 'error');
        }

        $church = $this->churchManager->findChurch($churchId);

        if ($church === null) {
            return redirect()
                ->route('migration.churches.index')
                ->with('status', 'Igreja não encontrada.')
                ->with('status_type', 'error');
        }

        try {
            $deleted = $this->churchManager->deleteProducts($church);
        } catch (\Throwable $exception) {
            return redirect()
                ->route('migration.churches.index')
                ->with('status', 'Erro ao excluir produtos: ' . $exception->getMessage())
                ->with('status_type', 'error');
        }

        return redirect()
            ->route('migration.churches.index')
            ->with('status', "Todos os {$deleted} produto(s) da igreja " . mb_strtoupper((string) $church->descricao, 'UTF-8') . ' foram excluídos.')
            ->with('status_type', 'success');
    }
}
