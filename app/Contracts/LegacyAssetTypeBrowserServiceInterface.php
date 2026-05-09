<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\AssetTypeFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LegacyAssetTypeBrowserServiceInterface
{
    public function paginate(AssetTypeFilters $filters): LengthAwarePaginator;

    public function countAll(): int;
}
