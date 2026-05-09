<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LegacyChurchBrowserServiceInterface;
use App\DTO\ChurchFilters;
use App\Models\Legacy\Administracao;
use App\Models\Legacy\Comum;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class LegacyChurchBrowserService implements LegacyChurchBrowserServiceInterface
{
    public function paginate(ChurchFilters $filters): LengthAwarePaginator
    {
        return Comum::query()
            ->with(['administracao:id,descricao'])
            ->withCount('activeProducts')
            ->when(
                $filters->search !== '',
                static function ($query) use ($filters): void {
                    $query->where(function ($nested) use ($filters): void {
                        $nested
                            ->where('codigo', 'like', '%' . $filters->search . '%')
                            ->orWhere('descricao', 'like', '%' . $filters->search . '%');
                    });
                }
            )
            ->orderBy('codigo')
            ->paginate(
                perPage: $filters->perPage,
                pageName: 'pagina',
                page: $filters->page,
            );
    }

    public function countAll(): int
    {
        return Comum::query()->count();
    }

    public function administrationOptions(): Collection
    {
        return Administracao::query()
            ->orderBy('descricao')
            ->get(['id', 'descricao']);
    }
}
