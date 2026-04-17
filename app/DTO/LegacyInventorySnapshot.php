<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class LegacyInventorySnapshot
{
    /**
     * @param array<string, int> $architectureCounts
     * @param array<int, LegacyModuleSummary> $modules
     */
    public function __construct(
        public string $legacyRootPath,
        public string $legacyPublicUrl,
        public bool $databaseReachable,
        public ?string $databaseDriver,
        public ?string $databaseName,
        public ?string $databaseError,
        public array $architectureCounts,
        public array $modules,
    ) {
    }
}
