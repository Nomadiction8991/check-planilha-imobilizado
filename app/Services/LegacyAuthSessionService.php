<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Models\Legacy\Administracao;
use App\Models\Legacy\Comum;
use App\Models\Legacy\Usuario;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use RuntimeException;

class LegacyAuthSessionService implements LegacyAuthSessionServiceInterface
{
    public function attempt(string $email, string $password): array
    {
        $normalizedEmail = mb_strtoupper(trim($email), 'UTF-8');

        /** @var Usuario|null $user */
        $user = Usuario::query()->active()->where('email', $normalizedEmail)->first();

        if ($user === null || !password_verify($password, (string) $user->senha)) {
            throw new RuntimeException('E-mail ou senha inválidos.');
        }

        $churchId = $this->resolveInitialChurchId($user);
        $permissions = $this->resolvePermissions($user);
        $administrationIds = $this->resolveAdministrationIds($user);

        Session::put([
            'usuario_id' => (int) $user->id,
            'usuario_nome' => (string) $user->nome,
            'usuario_email' => (string) $user->email,
            'comum_id' => $churchId,
            'administracao_id' => $this->resolveAdministrationId($user),
            'administracoes_permitidas' => $administrationIds,
            'is_admin' => $this->inferIsAdmin($user),
            'legacy_permissions' => $permissions,
        ]);
        Session::regenerate();

        return [
            'id' => (int) $user->id,
            'nome' => (string) $user->nome,
            'email' => (string) $user->email,
            'comum_id' => $churchId,
            'administracao_id' => $this->resolveAdministrationId($user),
            'administracoes_permitidas' => $administrationIds,
            'is_admin' => $this->inferIsAdmin($user),
            'legacy_permissions' => $permissions,
        ];
    }

    public function logout(): void
    {
        Session::invalidate();
        Session::regenerateToken();
    }

    public function isAuthenticated(): bool
    {
        return (int) Session::get('usuario_id', 0) > 0;
    }

    public function currentUser(): ?array
    {
        $userId = (int) Session::get('usuario_id', 0);
        if ($userId <= 0) {
            return null;
        }

        /** @var Usuario|null $user */
        $user = Usuario::query()->find($userId);
        if ($user === null) {
            return null;
        }

        return [
            'id' => $userId,
            'nome' => (string) ($user->nome ?: Session::get('usuario_nome', '')),
            'email' => (string) ($user->email ?: Session::get('usuario_email', '')),
            'comum_id' => Session::has('comum_id') ? (int) Session::get('comum_id') : null,
            'administracao_id' => Session::has('administracao_id') ? (int) Session::get('administracao_id') : $this->resolveAdministrationId($user),
            'administracoes_permitidas' => array_values(array_filter(array_map(
                static fn (mixed $value): int => (int) $value,
                (array) Session::get('administracoes_permitidas', []),
            ), static fn (int $value): bool => $value > 0)),
            'is_admin' => $user->isAdministrator(),
        ];
    }

    public function currentChurchId(): ?int
    {
        $churchId = (int) Session::get('comum_id', 0);

        if ($churchId > 0) {
            return $churchId;
        }

        if (!$this->isAuthenticated()) {
            return null;
        }

        $userId = (int) Session::get('usuario_id');
        /** @var Usuario|null $user */
        $user = Usuario::query()->find($userId);
        if ($user === null) {
            return null;
        }

        $churchId = $this->resolveInitialChurchId($user);

        if ($churchId !== null) {
            Session::put('comum_id', $churchId);
        } else {
            Session::forget('comum_id');
        }

        return $churchId;
    }

    public function switchChurch(int $churchId): void
    {
        if ($churchId <= 0) {
            throw new RuntimeException('Igreja inválida.');
        }

        /** @var Comum|null $church */
        $church = Comum::query()->find($churchId);
        if ($church === null) {
            throw new RuntimeException('Igreja não encontrada.');
        }

        Session::put('comum_id', $churchId);
    }

    public function currentChurch(): ?array
    {
        $churchId = $this->currentChurchId();
        if ($churchId === null) {
            return null;
        }

        /** @var Comum|null $church */
        $church = Comum::query()->find($churchId);
        if ($church === null) {
            return null;
        }

        return [
            'id' => (int) $church->id,
            'codigo' => (string) $church->codigo,
            'descricao' => (string) $church->descricao,
        ];
    }

    public function availableChurches(): Collection
    {
        return Comum::query()
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'descricao']);
    }

    private function resolveInitialChurchId(Usuario $user): ?int
    {
        if ($this->inferIsAdmin($user) || (int) ($user->administracao_id ?? 0) > 0) {
            return null;
        }

        $churchId = (int) ($user->comum_id ?? 0);
        if ($churchId > 0 && Comum::query()->whereKey($churchId)->exists()) {
            return $churchId;
        }

        /** @var Comum|null $firstChurch */
        $firstChurch = Comum::query()->orderBy('codigo')->first(['id']);
        if ($firstChurch === null) {
            return null;
        }

        Usuario::query()->whereKey($user->id)->update(['comum_id' => (int) $firstChurch->id]);

        return (int) $firstChurch->id;
    }

    private function resolveAdministrationId(Usuario $user): ?int
    {
        $administrationId = (int) ($user->administracao_id ?? 0);

        return $administrationId > 0 ? $administrationId : null;
    }

    /**
     * @return array<int, int>
     */
    private function resolveAdministrationIds(Usuario $user): array
    {
        if ($this->inferIsAdmin($user)) {
            return $this->allAdministrationIds();
        }

        $administrationIds = array_values(array_filter(array_map(
            static fn (mixed $value): int => (int) $value,
            (array) ($user->administracoes_permitidas ?? []),
        ), static fn (int $value): bool => $value > 0));

        $administrationId = $this->resolveAdministrationId($user);
        if ($administrationId !== null && !in_array($administrationId, $administrationIds, true)) {
            $administrationIds[] = $administrationId;
        }

        return array_values(array_unique($administrationIds));
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

    private function inferIsAdmin(Usuario $user): bool
    {
        return $user->isAdministrator();
    }

    /**
     * @return array<string, bool>
     */
    private function resolvePermissions(Usuario $user): array
    {
        $defaults = (array) config('legacy.permissions.defaults', []);

        if ($this->inferIsAdmin($user)) {
            return array_fill_keys(array_keys($defaults), true);
        }

        $stored = $user->permissions;
        if (!is_array($stored) || $stored === []) {
            return $defaults;
        }

        $permissions = $defaults;
        foreach ($defaults as $ability => $defaultValue) {
            $permissions[$ability] = array_key_exists($ability, $stored)
                ? (bool) $stored[$ability]
                : (bool) $defaultValue;
        }

        return $permissions;
    }
}
