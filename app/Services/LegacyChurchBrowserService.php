<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LegacyChurchBrowserServiceInterface;
use App\DTO\ChurchFilters;
use App\Models\Legacy\Administracao;
use App\Models\Legacy\Comum;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class LegacyChurchBrowserService implements LegacyChurchBrowserServiceInterface
{
    public function paginate(ChurchFilters $filters): LengthAwarePaginator
    {
        $administrationScopeIds = $this->currentAdministrationScopeIds();

        return Comum::query()
            ->with(['administracao:id,descricao'])
            ->withCount('activeProducts')
            ->when(
                $administrationScopeIds !== null,
                static fn ($query) => $query->whereIn('administracao_id', $administrationScopeIds),
            )
            ->when(
                $filters->administrationId !== null,
                static function ($query) use ($filters): void {
                    $query->where('administracao_id', $filters->administrationId);
                }
            )
            ->when(
                $filters->state !== null && $filters->state !== '',
                static function ($query) use ($filters): void {
                    $query->where('estado', $filters->state);
                }
            )
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
        $administrationScopeIds = $this->currentAdministrationScopeIds();

        return Comum::query()
            ->when(
                $administrationScopeIds !== null,
                static fn ($query) => $query->whereIn('administracao_id', $administrationScopeIds),
            )
            ->count();
    }

    public function administrationOptions(): Collection
    {
        $administrationScopeIds = $this->currentAdministrationScopeIds();

        return Administracao::query()
            ->when(
                $administrationScopeIds !== null,
                static fn ($query) => $query->whereIn('id', $administrationScopeIds),
            )
            ->orderBy('descricao')
            ->get(['id', 'descricao']);
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

        $permittedAdministrationIds = array_values(array_unique($permittedAdministrationIds));

        return $permittedAdministrationIds !== [] ? $permittedAdministrationIds : [];
    }
}
