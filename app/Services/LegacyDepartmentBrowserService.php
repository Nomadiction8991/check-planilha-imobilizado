<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LegacyDepartmentBrowserServiceInterface;
use App\DTO\DepartmentFilters;
use App\Models\Legacy\Comum;
use App\Models\Legacy\Dependencia;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class LegacyDepartmentBrowserService implements LegacyDepartmentBrowserServiceInterface
{
    public function paginate(DepartmentFilters $filters): LengthAwarePaginator
    {
        return Dependencia::query()
            ->with(['comum:id,codigo,descricao'])
            ->withCount(['activeProducts as active_products_count'])
            ->when(
                $filters->comumId !== null,
                static fn ($query) => $query->where('comum_id', $filters->comumId)
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

    public function countAll(): int
    {
        return Dependencia::query()->count();
    }
}
