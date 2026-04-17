<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\UserFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface LegacyUserBrowserServiceInterface
{
    public function paginate(UserFilters $filters): LengthAwarePaginator;

    public function administrationOptions(): Collection;

    /**
     * @return array<string, string>
     */
    public function statusOptions(): array;

    public function countAll(): int;
}
