<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class AssetTypeMutationData
{
    public function __construct(
        public string $description,
    ) {
    }
}
