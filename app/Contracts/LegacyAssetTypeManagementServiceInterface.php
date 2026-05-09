<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\AssetTypeMutationData;
use App\Models\Legacy\TipoBem;

interface LegacyAssetTypeManagementServiceInterface
{
    public function create(AssetTypeMutationData $data): TipoBem;

    public function update(TipoBem $assetType, AssetTypeMutationData $data): TipoBem;

    public function delete(TipoBem $assetType): void;
}
