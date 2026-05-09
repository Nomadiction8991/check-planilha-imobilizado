<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class ProductVerificationItemData
{
    public function __construct(
        public int $productId,
        public bool $printLabel,
        public bool $verified,
        public string $observation,
    ) {
    }
}
