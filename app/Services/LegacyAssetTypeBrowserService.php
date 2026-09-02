<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LegacyAssetTypeBrowserServiceInterface;
use App\DTO\AssetTypeFilters;
use App\Models\Legacy\Administracao;
use App\Models\Legacy\TipoBem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;

class LegacyAssetTypeBrowserService implements LegacyAssetTypeBrowserServiceInterface
{
    public function paginate(AssetTypeFilters $filters): LengthAwarePaginator
    {
        return $this->baseQuery()
            ->withCount(['activeProducts as active_products_count'])
            ->when(
                $filters->administrationId !== null && Schema::hasColumn('tipos_bens', 'administracao_id'),
                static function ($query) use ($filters): void {
                    $query->where('administracao_id', $filters->administrationId);
                }
            )
            ->when(
                $filters->state !== null && $filters->state !== '' && Schema::hasColumn('tipos_bens', 'administracao_id'),
                static function ($query) use ($filters): void {
                    $query->whereHas('administracao', static function ($adminQuery) use ($filters): void {
                        $adminQuery->where('estado', $filters->state);
                    });
                }
            )
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
        return $this->baseQuery()->count();
    }

    public function administrationOptions(): Collection
    {
        $query = Administracao::query()->orderBy('descricao');
        $administrationId = $this->currentAdministrationId();

        if (!$this->canManageOtherAdministrations() && $administrationId !== null) {
            $query->whereKey($administrationId);
        }

        return $query->get(['id', 'descricao']);
    }

    public function assetTypeOptions(): Collection
    {
        $supportsAdministrationScope = Schema::hasColumn('tipos_bens', 'administracao_id');
        $query = $this->baseQuery();

        if ($supportsAdministrationScope) {
            $query->with(['administracao:id,descricao']);
        }

        $select = ['id', 'codigo', 'descricao'];
        if ($supportsAdministrationScope) {
            $select[] = 'administracao_id';
        }

        return $query
            ->orderBy('codigo')
            ->orderBy('descricao')
            ->get($select);
    }

    private function baseQuery()
    {
        $query = TipoBem::query();

        if (!Schema::hasColumn('tipos_bens', 'administracao_id')) {
            return $query;
        }

        if ($this->canManageOtherAdministrations()) {
            return $query;
        }

        $administrationId = $this->currentAdministrationId();
        if ($administrationId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($nested) use ($administrationId): void {
            $nested
                ->where('administracao_id', $administrationId)
                ->orWhereNull('administracao_id');
        });
    }

    private function canManageOtherAdministrations(): bool
    {
        return (bool) Session::get('is_admin', false);
    }

    private function currentAdministrationId(): ?int
    {
        $administrationId = (int) Session::get('administracao_id', 0);

        return $administrationId > 0 ? $administrationId : null;
    }
}
