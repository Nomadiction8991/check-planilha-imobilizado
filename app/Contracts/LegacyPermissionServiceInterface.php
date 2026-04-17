<?php

declare(strict_types=1);

namespace App\Contracts;

interface LegacyPermissionServiceInterface
{
    public function can(string $ability): bool;

    /**
     * @return array<string, bool>
     */
    public function currentPermissions(): array;
}
