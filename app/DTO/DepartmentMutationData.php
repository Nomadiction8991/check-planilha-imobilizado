<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class DepartmentMutationData
{
    public function __construct(
        public int $churchId,
        public string $description,
    ) {
    }
}
