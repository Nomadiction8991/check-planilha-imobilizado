<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class UpdateLegacyProductData
{
    public function __construct(
        public int $editedAssetTypeId,
        public string $editedItemName,
        public string $editedComplement,
        public int $editedDependencyId,
        public bool $verified,
        public bool $printLabel,
        public string $observation,
        public bool $printReport141,
        public string $condition141,
        public ?int $invoiceNumber,
        public ?string $invoiceDate,
        public ?string $invoiceValue,
        public ?string $invoiceSupplier,
    ) {
    }
}
