<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class AdministrationMutationData
{
    public function __construct(
        public string $description,
        public string $cnpj,
        public string $state,
        public string $city,
    ) {
    }
}
