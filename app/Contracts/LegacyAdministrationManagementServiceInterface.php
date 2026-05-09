<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\AdministrationMutationData;
use App\Models\Legacy\Administracao;

interface LegacyAdministrationManagementServiceInterface
{
    public function create(AdministrationMutationData $data): Administracao;

    public function update(Administracao $administration, AdministrationMutationData $data): Administracao;

    public function delete(Administracao $administration): void;
}
