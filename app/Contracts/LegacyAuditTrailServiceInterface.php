<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\LegacyAuditEntryData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LegacyAuditTrailServiceInterface
{
    public function record(LegacyAuditEntryData $entry): void;

    /**
     * @param array<string, string> $filters
     * @param array<string, mixed> $query
     */
    public function paginate(
        array $filters,
        ?int $userId,
        ?int $administrationId,
        ?int $churchId,
        bool $isAdmin,
        string $path,
        array $query = [],
        int $page = 1,
        int $perPage = 20,
    ): LengthAwarePaginator;

    /**
     * @return array<int, string>
     */
    public function availableModules(): array;
}
