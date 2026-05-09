<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class ChurchMutationData
{
    public function __construct(
        public int $administrationId,
        public string $description,
        public string $cnpj,
        public string $state,
        public string $city,
        public string $administrationState,
        public string $administrationCity,
        public ?string $sector,
    ) {
    }
}
