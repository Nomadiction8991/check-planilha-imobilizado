<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class AssetTypeMutationData
{
    public function __construct(
        public ?int $administrationId,
        public string $description,
    ) {
    }
}
