<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LegacyDepartmentBrowserServiceInterface;
use App\DTO\DepartmentFilters;
use App\Models\Legacy\Administracao;
use App\Models\Legacy\Comum;
use App\Models\Legacy\Dependencia;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class LegacyDepartmentBrowserService implements LegacyDepartmentBrowserServiceInterface
{
    public function paginate(DepartmentFilters $filters): LengthAwarePaginator
    {
        $administrationScopeIds = $this->currentAdministrationScopeIds();

        return Dependencia::query()
            ->with(['comum:id,codigo,descricao,estado'])
            ->withCount(['activeProducts as active_products_count'])
            ->when(
                $administrationScopeIds !== null,
                static fn ($query) => $query->whereHas('comum', static fn ($churchQuery) => $churchQuery->whereIn('administracao_id', $administrationScopeIds))
            )
            ->when(
                $filters->administrationId !== null,
                static fn ($query) => $query->whereHas('comum', static fn ($churchQuery) => $churchQuery->where('administracao_id', $filters->administrationId))
            )
            ->when(
                $filters->comumId !== null,
                static fn ($query) => $query->where('comum_id', $filters->comumId)
            )
            ->when(
                $filters->state !== null && $filters->state !== '',
                static fn ($query) => $query->whereHas('comum', static fn ($churchQuery) => $churchQuery->where('estado', $filters->state))
            )
            ->when(
                $filters->search !== '',
                static fn ($query) => $query->where('descricao', 'like', '%' . $filters->search . '%')
            )
            ->orderBy('descricao')
            ->paginate(
                perPage: $filters->perPage,
                pageName: 'pagina',
                page: $filters->page,
            );
    }

    public function churchOptions(): Collection
    {
        $administrationScopeIds = $this->currentAdministrationScopeIds();

        return Comum::query()
            ->when(
                $administrationScopeIds !== null,
                static fn ($query) => $query->whereIn('administracao_id', $administrationScopeIds)
            )
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'descricao']);
    }

    public function administrationOptions(): Collection
    {
        $administrationScopeIds = $this->currentAdministrationScopeIds();

        return Administracao::query()
            ->when(
                $administrationScopeIds !== null,
                static fn ($query) => $query->whereIn('id', $administrationScopeIds)
            )
            ->orderBy('descricao')
            ->get(['id', 'descricao']);
    }

    public function countAll(): int
    {
        $administrationScopeIds = $this->currentAdministrationScopeIds();

        return Dependencia::query()
            ->when(
                $administrationScopeIds !== null,
                static fn ($query) => $query->whereHas('comum', static fn ($churchQuery) => $churchQuery->whereIn('administracao_id', $administrationScopeIds))
            )
            ->count();
    }

    /**
     * @return array<int, int>|null
     */
    private function currentAdministrationScopeIds(): ?array
    {
        if ((bool) Session::get('is_admin', false)) {
            return null;
        }

        if (!Session::has('is_admin')
            && !Session::has('administracao_id')
            && !Session::has('administracoes_permitidas')
        ) {
            return null;
        }

        $permittedAdministrationIds = array_values(array_filter(array_map(
            static fn (mixed $value): int => (int) $value,
            (array) Session::get('administracoes_permitidas', []),
        ), static fn (int $value): bool => $value > 0));

        $currentAdministrationId = (int) Session::get('administracao_id', 0);
        if ($currentAdministrationId > 0) {
            $permittedAdministrationIds[] = $currentAdministrationId;
        }

        return array_values(array_unique($permittedAdministrationIds));
    }
}
