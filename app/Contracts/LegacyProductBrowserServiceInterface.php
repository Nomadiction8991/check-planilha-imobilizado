<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\ProductFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface LegacyProductBrowserServiceInterface
{
    public function paginate(ProductFilters $filters): LengthAwarePaginator;

    public function churchOptions(): Collection;

    public function dependencyOptions(?int $comumId): Collection;

    public function assetTypeOptions(): Collection;

    /**
     * @return array<string, string>
     */
    public function statusOptions(): array;
}
