<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\LegacyInventorySnapshot;

interface LegacyInventoryServiceInterface
{
    public function buildSnapshot(): LegacyInventorySnapshot;
}
