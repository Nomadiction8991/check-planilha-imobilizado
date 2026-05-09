<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\LegacyAdministrationBrowserServiceInterface;
use App\Contracts\LegacyAdministrationManagementServiceInterface;
use App\DTO\AdministrationFilters;
use App\Http\Requests\StoreLegacyAdministrationRequest;
use App\Http\Requests\UpdateLegacyAdministrationRequest;
use App\Models\Legacy\Administracao;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class LegacyAdministrationController extends Controller
{
    public function __construct(
        private readonly LegacyAdministrationBrowserServiceInterface $administrations,
        private readonly LegacyAdministrationManagementServiceInterface $administrationManager,
    ) {
    }

    public function index(Request $request): View
    {
        $filters = AdministrationFilters::fromRequest($request);
        $paginator = $this->administrations->paginate($filters)->appends($filters->toQuery());

        return view('administrations.index', [
            'filters' => $filters,
            'administrations' => $paginator,
            'totalAll' => $this->administrations->countAll(),
        ]);
    }

    public function create(): View
    {
        return view('administrations.create');
    }

    public function store(StoreLegacyAdministrationRequest $request): RedirectResponse
    {
        try {
            $administration = $this->administrationManager->create($request->toDto());
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('migration.administrations.create')
                ->withInput()
                ->with('status', $exception->getMessage())
                ->with('status_type', 'error');
        }

        return redirect()
            ->route('migration.administrations.index')
            ->with('status', 'Administração criada com sucesso.')
            ->with('status_type', 'success')
            ->with('status_detail', 'ID gerado: ' . $administration->id . '.');
    }

    public function edit(Administracao $administration): View
    {
        return view('administrations.edit', [
            'administration' => $administration,
        ]);
    }

    public function update(UpdateLegacyAdministrationRequest $request, Administracao $administration): RedirectResponse
    {
        try {
            $this->administrationManager->update($administration, $request->toDto());
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('migration.administrations.edit', ['administration' => $administration->id])
                ->withInput()
                ->with('status', $exception->getMessage())
                ->with('status_type', 'error');
        }

        return redirect()
            ->route('migration.administrations.index')
            ->with('status', 'Administração atualizada com sucesso.')
            ->with('status_type', 'success');
    }

    public function destroy(Administracao $administration): RedirectResponse
    {
        try {
            $this->administrationManager->delete($administration);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('migration.administrations.index')
                ->with('status', $exception->getMessage())
                ->with('status_type', 'error');
        }

        return redirect()
            ->route('migration.administrations.index')
            ->with('status', 'Administração excluída com sucesso.')
            ->with('status_type', 'success');
    }
}
