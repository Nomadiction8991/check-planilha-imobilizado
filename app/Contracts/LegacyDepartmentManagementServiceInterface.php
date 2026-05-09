<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\DepartmentMutationData;
use App\Models\Legacy\Dependencia;

interface LegacyDepartmentManagementServiceInterface
{
    public function create(DepartmentMutationData $data): Dependencia;

    public function update(Dependencia $department, DepartmentMutationData $data): Dependencia;

    public function delete(Dependencia $department): void;
}
