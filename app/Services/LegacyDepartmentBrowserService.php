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

class LegacyDepartmentBrowserService implements LegacyDepartmentBrowserServiceInterface
{
    public function paginate(DepartmentFilters $filters): LengthAwarePaginator
    {
        return Dependencia::query()
            ->with(['comum:id,codigo,descricao,estado'])
            ->withCount(['activeProducts as active_products_count'])
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
        return Comum::query()
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'descricao']);
    }

    public function administrationOptions(): Collection
    {
        return Administracao::query()
            ->orderBy('descricao')
            ->get(['id', 'descricao']);
    }

    public function countAll(): int
    {
        return Dependencia::query()->count();
    }
}
