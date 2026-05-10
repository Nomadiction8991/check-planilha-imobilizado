<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyPermissionServiceInterface;
use App\Contracts\LegacyUserManagementServiceInterface;
use App\DTO\UserMutationData;
use App\Models\Legacy\Administracao;
use App\Models\Legacy\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class LegacyUserManagementService implements LegacyUserManagementServiceInterface
{
    public function __construct(
        private readonly LegacyAuthSessionServiceInterface $auth,
        private readonly LegacyPermissionServiceInterface $permissions,
    ) {
    }

    public function create(UserMutationData $data): Usuario
    {
        $this->assertAdministrationExists($data->administrationId);
        $this->assertAdministrationAllowed($data->administrationId);
        $normalized = $this->normalizePayload($data);
        $this->assertUniqueEmail($normalized['email']);
        $this->assertUniqueCpf($normalized['cpf']);

        return Usuario::query()->create($normalized);
    }

    public function update(Usuario $user, UserMutationData $data): Usuario
    {
        $this->assertUserCanBeUpdated($user);
        $this->assertAdministrationExists($data->administrationId);
        $this->assertAdministrationAllowed($data->administrationId, $user);
        $normalized = $this->normalizePayload($data, $user);
        $this->assertUniqueEmail($normalized['email'], (int) $user->getKey());
        $this->assertUniqueCpf($normalized['cpf'], (int) $user->getKey());

        $user->fill($normalized);
        $user->save();

        return $user->refresh();
    }

    public function updatePermissions(Usuario $user, array $permissions): Usuario
    {
        $this->assertUserCanBeUpdated($user);

        if (!$this->permissionsCanBeEdited()) {
            throw new RuntimeException('Você não tem permissão para gerenciar permissões de usuários.');
        }

        $user->permissions = $user->isAdministrator()
            ? $this->normalizePermissionSelection(array_keys($this->permissionDefaults()))
            : $this->normalizePermissionSelection(array_keys(array_filter($permissions)));
        $user->save();

        return $user->refresh();
    }

    public function delete(Usuario $user): void
    {
        $this->assertUserCanBeManaged($user);

        $currentUserId = (int) session('usuario_id', 0);

        if ($currentUserId > 0 && $currentUserId === (int) $user->getKey()) {
            throw new RuntimeException('Você não pode deletar sua própria conta.');
        }

        $user->delete();
    }

    /**
     * @return array<string, int|string|null|array<string, bool>>
     */
    private function normalizePayload(UserMutationData $data, ?Usuario $existingUser = null): array
    {
        $cpf = $this->digitsOnly($data->cpf);
        $phone = trim($data->phone);

        $payload = [
            'administracao_id' => $data->administrationId,
            'comum_id' => null,
            'nome' => mb_strtoupper(trim($data->name), 'UTF-8'),
            'email' => mb_strtoupper(trim($data->email), 'UTF-8'),
            'ativo' => $data->active ? 1 : 0,
            'cpf' => $cpf,
            'rg' => $data->rgEqualsCpf ? $cpf : $this->formatRg($data->rg),
            'rg_igual_cpf' => $data->rgEqualsCpf ? 1 : 0,
            'telefone' => mb_strtoupper($phone, 'UTF-8'),
            'casado' => $data->married ? 1 : 0,
            'nome_conjuge' => '',
            'cpf_conjuge' => '',
            'rg_conjuge' => '',
            'rg_conjuge_igual_cpf' => 0,
            'telefone_conjuge' => '',
            'endereco_cep' => trim($data->addressZip),
            'endereco_logradouro' => trim($data->addressStreet),
            'endereco_numero' => trim($data->addressNumber),
            'endereco_complemento' => trim($data->addressComplement),
            'endereco_bairro' => trim($data->addressDistrict),
            'endereco_cidade' => trim($data->addressCity),
            'endereco_estado' => mb_strtoupper(trim($data->addressState), 'UTF-8'),
            'permissions' => $this->resolvePermissionsPayload($data, $existingUser),
        ];

        $payload['administracoes_permitidas'] = $existingUser !== null && $existingUser->isAdministrator()
            ? $this->allAdministrationIds()
            : $this->normalizeAdministrationScopeIds($data->administrationIds, $data->administrationId);

        if ($existingUser !== null && $existingUser->isAdministrator()) {
            $payload['tipo'] = 'administrador';
        } elseif ($existingUser !== null && trim((string) ($existingUser->tipo ?? '')) !== '') {
            $payload['tipo'] = (string) $existingUser->tipo;
        }

        if ($data->married) {
            $spouseCpf = $this->digitsOnly($data->spouseCpf);

            $payload['nome_conjuge'] = mb_strtoupper(trim($data->spouseName), 'UTF-8');
            $payload['cpf_conjuge'] = $spouseCpf;
            $payload['rg_conjuge'] = $data->spouseRgEqualsCpf ? $spouseCpf : $this->formatRg($data->spouseRg);
            $payload['rg_conjuge_igual_cpf'] = $data->spouseRgEqualsCpf ? 1 : 0;
            $payload['telefone_conjuge'] = trim($data->spousePhone);
        }

        if ($data->password !== null) {
            $payload['senha'] = Hash::make($data->password);
        }

        return $payload;
    }

    private function assertAdministrationExists(?int $administrationId): void
    {
        if ($administrationId === null) {
            throw new RuntimeException('Selecione uma administração válida.');
        }

        if (!Administracao::query()->whereKey($administrationId)->exists()) {
            throw new RuntimeException('A administração selecionada não está mais disponível.');
        }
    }

    private function assertUniqueEmail(string $email, ?int $ignoreUserId = null): void
    {
        $exists = Usuario::query()
            ->whereRaw('UPPER(email) = ?', [$email])
            ->when(
                $ignoreUserId !== null,
                static fn (Builder $query) => $query->whereKeyNot($ignoreUserId)
            )
            ->exists();

        if ($exists) {
            throw new RuntimeException($ignoreUserId === null ? 'E-mail já cadastrado.' : 'E-mail já cadastrado por outro usuário.');
        }
    }

    private function assertUniqueCpf(string $cpf, ?int $ignoreUserId = null): void
    {
        $exists = Usuario::query()
            ->where('cpf', $cpf)
            ->when(
                $ignoreUserId !== null,
                static fn (Builder $query) => $query->whereKeyNot($ignoreUserId)
            )
            ->exists();

        if ($exists) {
            throw new RuntimeException($ignoreUserId === null ? 'CPF já cadastrado.' : 'CPF já cadastrado por outro usuário.');
        }
    }

    private function assertUserCanBeManaged(Usuario $user): void
    {
        if ($user->isProtectedAdministratorAccount()) {
            throw new RuntimeException('O usuário administrador não pode ser editado ou excluído.');
        }

        $this->assertAdministrationAllowed((int) ($user->administracao_id ?? 0) > 0 ? (int) $user->administracao_id : null, $user);
    }

    private function assertUserCanBeUpdated(Usuario $user): void
    {
        $this->assertAdministrationAllowed((int) ($user->administracao_id ?? 0) > 0 ? (int) $user->administracao_id : null, $user);
    }

    private function assertAdministrationAllowed(?int $administrationId, ?Usuario $currentUser = null): void
    {
        if ($this->canManageOtherAdministrations()) {
            return;
        }

        $currentAdministrationId = $this->currentAdministrationId();
        if ($currentAdministrationId === null) {
            throw new RuntimeException('Você não tem uma administração ativa para cadastrar usuários.');
        }

        if ($currentUser !== null) {
            $existingAdministrationId = (int) ($currentUser->administracao_id ?? 0);
            if ($existingAdministrationId > 0 && $existingAdministrationId !== $currentAdministrationId) {
                throw new RuntimeException('Você só pode cadastrar ou editar usuários da sua própria administração.');
            }
        }

        if ($administrationId !== null && $administrationId !== $currentAdministrationId) {
            throw new RuntimeException('Você só pode cadastrar ou editar usuários da sua própria administração.');
        }
    }

    /**
     * @param array<int, int> $administrationIds
     * @return array<int, int>
     */
    private function normalizeAdministrationScopeIds(array $administrationIds, ?int $primaryAdministrationId): array
    {
        $selectedIds = array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): int => (int) $value,
            $administrationIds,
        ), static fn (int $value): bool => $value > 0)));

        if ($primaryAdministrationId !== null && !in_array($primaryAdministrationId, $selectedIds, true)) {
            array_unshift($selectedIds, $primaryAdministrationId);
        }

        if (!$this->canManageOtherAdministrations()) {
            $currentAdministrationId = $this->currentAdministrationId();
            if ($currentAdministrationId === null) {
                throw new RuntimeException('Você não tem uma administração ativa para cadastrar usuários.');
            }

            $invalidAdministrations = array_diff($selectedIds, [$currentAdministrationId]);
            if ($invalidAdministrations !== []) {
                throw new RuntimeException('Você só pode cadastrar ou editar usuários da sua própria administração.');
            }

            return [$currentAdministrationId];
        }

        return array_values(array_unique($selectedIds));
    }

    private function digitsOnly(string $value): string
    {
        $digits = preg_replace('/\D/', '', $value);

        return is_string($digits) ? $digits : '';
    }

    private function formatRg(string $value): string
    {
        $digits = $this->digitsOnly($value);

        if (strlen($digits) <= 1) {
            return $digits;
        }

        return substr($digits, 0, -1) . '-' . substr($digits, -1);
    }

    /**
     * @return array<string, bool>
     */
    private function resolvePermissionsPayload(UserMutationData $data, ?Usuario $existingUser = null): array
    {
        $defaults = $this->permissionDefaults();

        if ($existingUser !== null && $existingUser->isAdministrator()) {
            return $this->normalizePermissionSelection(array_keys($defaults));
        }

        if (!$this->permissionsCanBeEdited()) {
            return $existingUser !== null && is_array($existingUser->permissions)
                ? $this->normalizePermissionSelection(array_keys(array_filter($existingUser->permissions)))
                : $defaults;
        }

        if (!$data->permissionsProvided) {
            return $existingUser !== null && is_array($existingUser->permissions)
                ? $this->normalizePermissionSelection(array_keys(array_filter($existingUser->permissions)))
                : $defaults;
        }

        return $this->normalizePermissionSelection($data->permissions);
    }

    /**
     * @return array<string, bool>
     */
    private function normalizePermissionSelection(array $selectedPermissions): array
    {
        $defaults = $this->permissionDefaults();
        $selected = array_fill_keys($selectedPermissions, true);

        $permissions = [];
        foreach ($defaults as $ability => $defaultValue) {
            $permissions[$ability] = isset($selected[$ability]) ? true : false;
        }

        return $permissions;
    }

    /**
     * @return array<int, int>
     */
    protected function allAdministrationIds(): array
    {
        return Administracao::query()
            ->orderBy('descricao')
            ->pluck('id')
            ->map(static fn (mixed $value): int => (int) $value)
            ->filter(static fn (int $value): bool => $value > 0)
            ->values()
            ->all();
    }

    /**
     * @return array<string, bool>
     */
    private function permissionDefaults(): array
    {
        return (array) config('legacy.permissions.defaults', []);
    }

    private function permissionsCanBeEdited(): bool
    {
        return (bool) $this->permissions->can('users.permissions.manage');
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
