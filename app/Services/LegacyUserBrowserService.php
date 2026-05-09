<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyPermissionServiceInterface;
use App\Contracts\LegacyUserBrowserServiceInterface;
use App\DTO\UserFilters;
use App\Models\Legacy\Administracao;
use App\Models\Legacy\Usuario;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class LegacyUserBrowserService implements LegacyUserBrowserServiceInterface
{
    public function __construct(
        private readonly LegacyAuthSessionServiceInterface $auth,
        private readonly LegacyPermissionServiceInterface $permissions,
    ) {
    }

    public function paginate(UserFilters $filters): LengthAwarePaginator
    {
        $scopeAdministrationId = $this->scopeAdministrationId($filters->administrationId);

        return Usuario::query()
            ->with(['administracao:id,descricao'])
            ->when($scopeAdministrationId !== null, static fn ($query) => $query->where('administracao_id', $scopeAdministrationId))
            ->when(
                $filters->search !== '',
                static function ($query) use ($filters): void {
                    $query->where(function ($nested) use ($filters): void {
                        $nested
                            ->where('nome', 'like', '%' . $filters->search . '%')
                            ->orWhere('email', 'like', '%' . $filters->search . '%');
                    });
                }
            )
            ->when(
                $filters->status !== '',
                static fn ($query) => $query->where('ativo', (int) $filters->status)
            )
            ->orderBy('nome')
            ->paginate(
                perPage: $filters->perPage,
                pageName: 'pagina',
                page: $filters->page,
            );
    }

    public function administrationOptions(): Collection
    {
        if ($this->canManageOtherAdministrations()) {
            return Administracao::query()
                ->orderBy('descricao')
                ->get(['id', 'descricao']);
        }

        $administrationId = $this->currentAdministrationId();

        if ($administrationId === null) {
            return collect();
        }

        return Administracao::query()
            ->whereKey($administrationId)
            ->orderBy('descricao')
            ->get(['id', 'descricao']);
    }

    public function statusOptions(): array
    {
        return [
            '1' => 'Ativos',
            '0' => 'Inativos',
        ];
    }

    public function countAll(): int
    {
        $scopeAdministrationId = $this->scopeAdministrationId(null);

        return Usuario::query()
            ->when($scopeAdministrationId !== null, static fn ($query) => $query->where('administracao_id', $scopeAdministrationId))
            ->count();
    }

    private function scopeAdministrationId(?int $requestedAdministrationId): ?int
    {
        if ($this->canManageOtherAdministrations()) {
            return $requestedAdministrationId;
        }

        return $this->currentAdministrationId();
    }

    private function canManageOtherAdministrations(): bool
    {
        $currentUser = $this->auth->currentUser();

        if (($currentUser['is_admin'] ?? false) === true) {
            return true;
        }

        return (bool) $this->permissions->can('users.manage_other_administrations');
    }

    private function currentAdministrationId(): ?int
    {
        $currentUser = $this->auth->currentUser();
        $administrationId = (int) ($currentUser['administracao_id'] ?? 0);

        return $administrationId > 0 ? $administrationId : null;
    }
}
