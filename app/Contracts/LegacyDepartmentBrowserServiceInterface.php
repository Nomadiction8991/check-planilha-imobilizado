<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\DepartmentFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface LegacyDepartmentBrowserServiceInterface
{
    public function paginate(DepartmentFilters $filters): LengthAwarePaginator;

    public function churchOptions(): Collection;

    public function countAll(): int;
}
