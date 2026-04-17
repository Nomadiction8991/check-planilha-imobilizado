<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class LegacyModuleSummary
{
    public function __construct(
        public string $key,
        public string $title,
        public string $description,
        public string $category,
        public string $tone,
        public string $legacyPath,
        public string $target,
        public ?int $records = null,
    ) {
    }
}
