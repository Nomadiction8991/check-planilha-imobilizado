<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LegacyPermissionServiceInterface;
use App\Models\Legacy\Usuario;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Throwable;

class LegacyPermissionService implements LegacyPermissionServiceInterface
{
    private ?string $cachedSignature = null;

    /**
     * @var array<string, bool>|null
     */
    private ?array $cachedPermissions = null;

    /**
     * @return array<string, bool>
     */
    public function currentPermissions(): array
    {
        $signature = $this->permissionSignature();

        if ($this->cachedSignature === $signature && is_array($this->cachedPermissions)) {
            return $this->cachedPermissions;
        }

        $userId = (int) Session::get('usuario_id', 0);
        $cacheKey = "user_permissions_{$userId}_".md5($signature);

        $permissions = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($userId) {
            $perms = $this->resolvePermissions($userId);
            foreach ($this->permissionAliases() as $alias => $abilities) {
                $perms[$alias] = $this->anyGranted($perms, $abilities);
            }

            return $perms;
        });

        $this->cachedSignature = $signature;
        $this->cachedPermissions = $permissions;

        return $permissions;
    }

    public function can(string $ability): bool
    {
        $permissions = $this->currentPermissions();

        return $permissions[$ability] ?? false;
    }

    private function isAdmin(): bool
    {
        return (bool) Session::get('is_admin', false);
    }

    private function permissionSignature(): string
    {
        $userId = (int) Session::get('usuario_id', 0);
        $isAdmin = (bool) Session::get('is_admin', false);
        $storedPermissions = Session::get('legacy_permissions', []);
        $activeStatus = $this->activeUserStatus($userId);

        return $userId.'|'.(int) $isAdmin.'|'.(is_array($storedPermissions) ? md5(serialize($storedPermissions)) : 'na').'|'.$activeStatus;
    }

    private function activeUserStatus(int $userId): string
    {
        if ($userId <= 0) {
            return 'none';
        }

        try {
            $user = Usuario::query()->whereKey($userId)->first(['ativo']);
        } catch (Throwable) {
            return 'unknown';
        }

        if ($user === null) {
            return 'missing';
        }

        return (int) $user->ativo === 1 ? 'active' : 'inactive';
    }

    /**
     * @return array<string, bool>
     */
    private function resolvePermissions(int $userId): array
    {
        $defaults = $this->defaultPermissions();

        if ($userId > 0) {
            /** @var Usuario|null $user */
            $user = null;

            try {
                $user = Usuario::query()->whereKey($userId)->first();
            } catch (Throwable) {
                $user = null;
            }

            if ($user !== null && (int) $user->ativo !== 1) {
                // Registro existe e está inativo: nega tudo, inclusive os atalhos
                // de sessão (is_admin / legacy_permissions) — é justamente o caso
                // de conta desativada com sessão ainda aberta.
                return array_fill_keys(array_keys($defaults), false);
            }

            if ($user !== null && $user->isAdministrator()) {
                return array_fill_keys(array_keys($defaults), true);
            }

            if ($user !== null) {
                $storedPermissions = $user->permissions;
                if (is_array($storedPermissions)) {
                    return $this->mergePermissionValues($defaults, $storedPermissions);
                }
            }
            // Sem registro persistido não dá para afirmar inatividade: segue o
            // comportamento anterior e cai nos flags da sessão (is_admin /
            // legacy_permissions), sem introduzir bloqueio novo.
        }

        if ($this->isAdmin()) {
            return array_fill_keys(array_keys($defaults), true);
        }

        $storedPermissions = Session::get('legacy_permissions', []);
        if (is_array($storedPermissions)) {
            return $this->mergePermissionValues($defaults, $storedPermissions);
        }

        return $defaults;
    }

    /**
     * @return array<string, bool>
     */
    private function defaultPermissions(): array
    {
        return (array) config('legacy.permissions.defaults', []);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function permissionAliases(): array
    {
        return (array) config('legacy.permissions.aliases', []);
    }

    /**
     * @param  array<string, bool>  $defaults
     * @param  array<string, mixed>  $storedPermissions
     * @return array<string, bool>
     */
    private function mergePermissionValues(array $defaults, array $storedPermissions): array
    {
        $permissions = [];

        foreach ($defaults as $ability => $defaultValue) {
            $permissions[$ability] = array_key_exists($ability, $storedPermissions)
                ? (bool) $storedPermissions[$ability]
                : (bool) $defaultValue;
        }

        return $permissions;
    }

    /**
     * @param  array<string, bool>  $permissions
     * @param  array<int, string>  $abilities
     */
    private function anyGranted(array $permissions, array $abilities): bool
    {
        foreach ($abilities as $ability) {
            if (! empty($permissions[$ability])) {
                return true;
            }
        }

        return false;
    }
}
