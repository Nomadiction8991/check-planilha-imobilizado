<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class CreateLegacyProductData
{
    public function __construct(
        public int $churchId,
        public ?string $code,
        public int $assetTypeId,
        public string $itemName,
        public string $complement,
        public ?string $heightMeters,
        public ?string $widthMeters,
        public ?string $lengthMeters,
        public int $dependencyId,
        public int $multiplier,
        public bool $printReport141,
        public string $condition141,
        public ?int $invoiceNumber,
        public ?string $invoiceDate,
        public ?string $invoiceValue,
        public ?string $invoiceSupplier,
    ) {
    }
}
