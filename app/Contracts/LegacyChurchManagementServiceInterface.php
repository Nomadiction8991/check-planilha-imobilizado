<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\ChurchMutationData;
use App\Models\Legacy\Comum;

interface LegacyChurchManagementServiceInterface
{
    public function update(Comum $church, ChurchMutationData $data): Comum;

    public function findChurch(int $churchId): ?Comum;

    public function countProducts(int $churchId): int;

    public function deleteProducts(Comum $church): int;
}
