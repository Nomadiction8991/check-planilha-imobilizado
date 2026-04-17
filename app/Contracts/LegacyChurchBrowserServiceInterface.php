<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\ChurchFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LegacyChurchBrowserServiceInterface
{
    public function paginate(ChurchFilters $filters): LengthAwarePaginator;

    public function countAll(): int;
}
