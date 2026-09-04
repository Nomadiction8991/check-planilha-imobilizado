<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LegacyProductBrowserServiceInterface;
use App\DTO\ProductFilters;
use App\Models\Legacy\Administracao;
use App\Models\Legacy\Comum;
use App\Models\Legacy\Dependencia;
use App\Models\Legacy\Produto;
use App\Models\Legacy\TipoBem;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;

class LegacyProductBrowserService implements LegacyProductBrowserServiceInterface
{
    public function paginate(ProductFilters $filters): LengthAwarePaginator
    {
        $administrationScopeIds = $this->currentAdministrationScopeIds();

        return Produto::query()
            ->active()
            ->with([
                'comum:id,codigo,descricao',
                'dependencia:id,descricao',
                'tipoBem:id,codigo,descricao',
                'editadoDependencia:id,descricao',
                'editadoTipoBem:id,codigo,descricao',
            ])
            ->when(
                $administrationScopeIds !== null,
                static fn ($query) => $query->whereHas(
                    'comum',
                    static fn ($churchQuery) => $churchQuery->whereIn('administracao_id', $administrationScopeIds),
                )
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
                function (Builder $query) use ($filters): void {
                    $search = $filters->search;

                    $query->where(function (Builder $nested) use ($search): void {
                        $nested
                            ->where('codigo', 'like', '%' . $search . '%')
                            ->orWhere('bem', 'like', '%' . $search . '%')
                            ->orWhere('complemento', 'like', '%' . $search . '%')
                            ->orWhere(function (Builder $current) use ($search): void {
                                $this->whereCurrentClassification(
                                    $current,
                                    'dependencia',
                                    'editadoDependencia',
                                    static function (Builder $relationQuery) use ($search): void {
                                        $relationQuery->where('descricao', 'like', '%' . $search . '%');
                                    },
                                    false,
                                );
                            })
                            ->orWhere(function (Builder $current) use ($search): void {
                                $this->whereCurrentClassification(
                                    $current,
                                    'tipoBem',
                                    'editadoTipoBem',
                                    static function (Builder $relationQuery) use ($search): void {
                                        $relationQuery->where(function (Builder $match) use ($search): void {
                                            $match
                                                ->where('codigo', 'like', '%' . $search . '%')
                                                ->orWhere('descricao', 'like', '%' . $search . '%');
                                        });
                                    },
                                    true,
                                );
                            });
                    });
                }
            )
            ->when(
                $filters->dependencyId !== null,
                function (Builder $query) use ($filters): void {
                    $this->whereCurrentClassification(
                        $query,
                        'dependencia',
                        'editadoDependencia',
                        static fn (Builder $relationQuery) => $relationQuery->whereKey($filters->dependencyId),
                        false,
                    );
                }
            )
            ->when(
                $filters->assetTypeId !== null,
                function (Builder $query) use ($filters): void {
                    $this->whereCurrentClassification(
                        $query,
                        'tipoBem',
                        'editadoTipoBem',
                        static fn (Builder $relationQuery) => $relationQuery->whereKey($filters->assetTypeId),
                        true,
                    );
                }
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
            ->orderByRaw("CASE WHEN codigo IS NULL OR codigo = '' THEN 1 ELSE 0 END")
            ->orderBy('codigo')
            ->orderBy('id_produto')
            ->paginate(
                perPage: $filters->perPage,
                pageName: 'pagina',
                page: $filters->page,
            );
    }

    private function whereCurrentClassification(
        Builder $query,
        string $originalRelation,
        string $editedRelation,
        Closure $relationConstraint,
        bool $relationHasCode = false,
    ): void {
        $query->where(function (Builder $current) use ($originalRelation, $editedRelation, $relationConstraint, $relationHasCode): void {
            $current
                ->where(function (Builder $edited) use ($editedRelation, $relationConstraint, $relationHasCode): void {
                    $edited
                        ->where('editado', 1)
                        ->whereHas($editedRelation, function (Builder $related) use ($relationConstraint, $relationHasCode): void {
                            $this->whereRelationHasDisplayValue($related, $relationHasCode);
                            $relationConstraint($related);
                        });
                })
                ->orWhere(function (Builder $original) use ($originalRelation, $editedRelation, $relationConstraint, $relationHasCode): void {
                    $original
                        ->where(function (Builder $fallback) use ($editedRelation, $relationHasCode): void {
                            $fallback
                                ->where('editado', '!=', 1)
                                ->orWhereNull('editado')
                                ->orWhereDoesntHave($editedRelation, function (Builder $related) use ($relationHasCode): void {
                                    $this->whereRelationHasDisplayValue($related, $relationHasCode);
                                });
                        })
                        ->whereHas($originalRelation, $relationConstraint);
                });
        });
    }

    private function whereRelationHasDisplayValue(Builder $query, bool $relationHasCode): void
    {
        $query->where(function (Builder $display) use ($relationHasCode): void {
            $display
                ->whereNotNull('descricao')
                ->where('descricao', '!=', '');

            if ($relationHasCode) {
                $display->orWhere(function (Builder $code): void {
                    $code
                        ->whereNotNull('codigo')
                        ->where('codigo', '!=', '');
                });
            }
        });
    }

    public function churchOptions(): Collection
    {
        $administrationScopeIds = $this->currentAdministrationScopeIds();

        return Comum::query()
            ->when(
                $administrationScopeIds !== null,
                static fn ($query) => $query->whereIn('administracao_id', $administrationScopeIds),
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
                static fn ($query) => $query->whereIn('id', $administrationScopeIds),
            )
            ->orderBy('descricao')
            ->get(['id', 'descricao']);
    }

    public function dependencyOptions(?int $comumId): Collection
    {
        $administrationScopeIds = $this->currentAdministrationScopeIds();

        return Dependencia::query()
            ->when(
                $administrationScopeIds !== null,
                static fn ($query) => $query->whereHas(
                    'comum',
                    static fn ($churchQuery) => $churchQuery->whereIn('administracao_id', $administrationScopeIds),
                )
            )
            ->when(
                $comumId !== null,
                static fn ($query) => $query->where('comum_id', $comumId)
            )
            ->orderBy('descricao')
            ->get(['id', 'comum_id', 'descricao']);
    }

    public function assetTypeOptions(): Collection
    {
        $supportsAdministrationScope = Schema::hasColumn('tipos_bens', 'administracao_id');
        $administrationScopeIds = $this->currentAdministrationScopeIds();
        $query = TipoBem::query();

        if ($supportsAdministrationScope && $administrationScopeIds !== null) {
            $query->where(function ($nested) use ($administrationScopeIds): void {
                if ($administrationScopeIds !== []) {
                    $nested->whereIn('administracao_id', $administrationScopeIds);
                }

                $nested->orWhereNull('administracao_id');
            });
        }

        $select = ['id', 'codigo', 'descricao'];
        if ($supportsAdministrationScope) {
            $select[] = 'administracao_id';
            $query->with(['administracao:id,descricao']);
        }

        return $query
            ->orderBy('codigo')
            ->orderBy('descricao')
            ->get($select);
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
