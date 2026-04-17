<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\CreateLegacyProductData;
use App\DTO\UpdateLegacyProductData;
use App\Models\Legacy\Produto;

interface LegacyProductManagementServiceInterface
{
    public function createMany(CreateLegacyProductData $data): int;

    public function update(Produto $product, UpdateLegacyProductData $data): Produto;
}
