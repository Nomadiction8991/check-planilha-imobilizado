<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\LegacyDepartmentBrowserServiceInterface;
use App\Contracts\LegacyDepartmentManagementServiceInterface;
use App\DTO\DepartmentFilters;
use App\Http\Requests\StoreLegacyDepartmentRequest;
use App\Http\Requests\UpdateLegacyDepartmentRequest;
use App\Models\Legacy\Dependencia;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class LegacyDepartmentController extends Controller
{
    public function __construct(
        private readonly LegacyDepartmentBrowserServiceInterface $departments,
        private readonly LegacyDepartmentManagementServiceInterface $departmentManager,
    ) {
    }

    public function index(Request $request): View
    {
        $filters = DepartmentFilters::fromRequest($request);
        $paginator = $this->departments->paginate($filters)->appends($filters->toQuery());

        return view('departments.index', [
            'filters' => $filters,
            'departments' => $paginator,
            'churches' => $this->departments->churchOptions(),
            'totalAll' => $this->departments->countAll(),
        ]);
    }

    public function create(): View
    {
        return view('departments.create', [
            'churches' => $this->departments->churchOptions(),
        ]);
    }

    public function store(StoreLegacyDepartmentRequest $request): RedirectResponse
    {
        try {
            $department = $this->departmentManager->create($request->toDto());
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('migration.departments.create')
                ->withInput()
                ->with('status', $exception->getMessage())
                ->with('status_type', 'error');
        }

        return redirect()
            ->route('migration.departments.index')
            ->with('status', 'Dependência criada com sucesso.')
            ->with('status_type', 'success')
            ->with('status_detail', 'ID gerado: ' . $department->id . '.');
    }

    public function edit(Dependencia $department): View
    {
        return view('departments.edit', [
            'department' => $department->load('comum:id,codigo,descricao'),
            'churches' => $this->departments->churchOptions(),
        ]);
    }

    public function update(UpdateLegacyDepartmentRequest $request, Dependencia $department): RedirectResponse
    {
        try {
            $this->departmentManager->update($department, $request->toDto());
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('migration.departments.edit', ['department' => $department->id])
                ->withInput()
                ->with('status', $exception->getMessage())
                ->with('status_type', 'error');
        }

        return redirect()
            ->route('migration.departments.index')
            ->with('status', 'Dependência atualizada com sucesso.')
            ->with('status_type', 'success');
    }

    public function destroy(Dependencia $department): RedirectResponse
    {
        try {
            $this->departmentManager->delete($department);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('migration.departments.index')
                ->with('status', $exception->getMessage())
                ->with('status_type', 'error');
        }

        return redirect()
            ->route('migration.departments.index')
            ->with('status', 'Dependência excluída com sucesso.')
            ->with('status_type', 'success');
    }
}
