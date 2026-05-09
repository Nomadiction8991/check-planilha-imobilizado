<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\UserMutationData;
use App\Models\Legacy\Usuario;

interface LegacyUserManagementServiceInterface
{
    public function create(UserMutationData $data): Usuario;

    public function update(Usuario $user, UserMutationData $data): Usuario;

    public function delete(Usuario $user): void;
}
