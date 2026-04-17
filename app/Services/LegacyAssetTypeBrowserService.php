<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LegacyAssetTypeBrowserServiceInterface;
use App\DTO\AssetTypeFilters;
use App\Models\Legacy\TipoBem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LegacyAssetTypeBrowserService implements LegacyAssetTypeBrowserServiceInterface
{
    public function paginate(AssetTypeFilters $filters): LengthAwarePaginator
    {
        return TipoBem::query()
            ->withCount(['activeProducts as active_products_count'])
            ->when(
                $filters->search !== '',
                static function ($query) use ($filters): void {
                    $query->where(function ($nested) use ($filters): void {
                        $nested
                            ->where('descricao', 'like', '%' . $filters->search . '%')
                            ->orWhere('codigo', 'like', '%' . $filters->search . '%');
                    });
                }
            )
            ->orderBy('codigo')
            ->orderBy('descricao')
            ->paginate(
                perPage: $filters->perPage,
                pageName: 'pagina',
                page: $filters->page,
            );
    }

    public function countAll(): int
    {
        return TipoBem::query()->count();
    }
}
