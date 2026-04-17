<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\AdministrationFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LegacyAdministrationBrowserServiceInterface
{
    public function paginate(AdministrationFilters $filters): LengthAwarePaginator;

    public function countAll(): int;
}
