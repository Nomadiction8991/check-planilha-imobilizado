<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LegacyProductBrowserServiceInterface;
use App\DTO\ProductFilters;
use App\Models\Legacy\Comum;
use App\Models\Legacy\Dependencia;
use App\Models\Legacy\Produto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use App\Models\Legacy\TipoBem;

class LegacyProductBrowserService implements LegacyProductBrowserServiceInterface
{
    public function paginate(ProductFilters $filters): LengthAwarePaginator
    {
        return Produto::query()
            ->active()
            ->with([
                'comum:id,codigo,descricao',
                'dependencia:id,descricao',
                'tipoBem:id,codigo,descricao',
            ])
            ->when(
                $filters->comumId !== null,
                static fn ($query) => $query->where('comum_id', $filters->comumId)
            )
            ->when(
                $filters->search !== '',
                static function ($query) use ($filters): void {
                    $query->where(function ($nested) use ($filters): void {
                        $search = $filters->search;

                        $nested
                            ->where('codigo', 'like', '%' . $search . '%')
                            ->orWhere('bem', 'like', '%' . $search . '%')
                            ->orWhere('complemento', 'like', '%' . $search . '%')
                            ->orWhereHas('dependencia', static function ($dependencyQuery) use ($search): void {
                                $dependencyQuery->where('descricao', 'like', '%' . $search . '%');
                            })
                            ->orWhereHas('tipoBem', static function ($assetTypeQuery) use ($search): void {
                                $assetTypeQuery->where(function ($nestedAssetTypeQuery) use ($search): void {
                                    $nestedAssetTypeQuery
                                        ->where('codigo', 'like', '%' . $search . '%')
                                        ->orWhere('descricao', 'like', '%' . $search . '%');
                                });
                            });
                    });
                }
            )
            ->when(
                $filters->dependencyId !== null,
                static fn ($query) => $query->where('dependencia_id', $filters->dependencyId)
            )
            ->when(
                $filters->assetTypeId !== null,
                static fn ($query) => $query->where('tipo_bem_id', $filters->assetTypeId)
            )
            ->when(
                $filters->onlyNew,
                static fn ($query) => $query->where('novo', 1)
            )
            ->when(
                $filters->status !== '',
                function ($query) use ($filters): void {
                    match ($filters->status) {
                        'com_nota' => $query->whereNotNull('nota_numero')->where('nota_numero', '!=', ''),
                        'com_14_1' => $query->where('imprimir_14_1', 1),
                        'novos' => $query->where('novo', 1),
                        'sem_status' => $query->where(function ($nested): void {
                            $nested
                                ->whereNull('nota_numero')
                                ->orWhere('nota_numero', '=', '');
                        })->where('imprimir_14_1', 0),
                        default => null,
                    };
                }
            )
            ->orderByRaw('CASE WHEN codigo IS NULL OR codigo = "" THEN 1 ELSE 0 END')
            ->orderBy('codigo')
            ->orderBy('id_produto')
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

    public function dependencyOptions(?int $comumId): Collection
    {
        return Dependencia::query()
            ->when(
                $comumId !== null,
                static fn ($query) => $query->where('comum_id', $comumId)
            )
            ->orderBy('descricao')
            ->get(['id', 'comum_id', 'descricao']);
    }

    public function assetTypeOptions(): Collection
    {
        return TipoBem::query()
            ->orderBy('codigo')
            ->orderBy('descricao')
            ->get(['id', 'codigo', 'descricao']);
    }

    public function statusOptions(): array
    {
        return [
            'com_nota' => 'Com nota fiscal',
            'com_14_1' => 'Marcados para 14.1',
            'novos' => 'Somente novos',
            'sem_status' => 'Sem status',
        ];
    }
}
