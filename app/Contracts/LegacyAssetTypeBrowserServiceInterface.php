<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\AssetTypeFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface LegacyAssetTypeBrowserServiceInterface
{
    public function paginate(AssetTypeFilters $filters): LengthAwarePaginator;

    public function countAll(): int;

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\Legacy\Administracao>
     */
    public function administrationOptions(): Collection;
}
