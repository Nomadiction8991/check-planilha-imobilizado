<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class CnpjLookupData
{
    public function __construct(
        public string $cnpj,
    ) {
    }
}