<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LegacyAssetTypeManagementServiceInterface;
use App\DTO\AssetTypeMutationData;
use App\Models\Legacy\TipoBem;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LegacyAssetTypeManagementService implements LegacyAssetTypeManagementServiceInterface
{
    public function create(AssetTypeMutationData $data): TipoBem
    {
        return DB::transaction(function () use ($data): TipoBem {
            $nextCode = $this->nextCode();

            return TipoBem::query()->create([
                'codigo' => $nextCode,
                'descricao' => $data->description,
            ]);
        });
    }

    public function update(TipoBem $assetType, AssetTypeMutationData $data): TipoBem
    {
        $assetType->fill([
            'descricao' => $data->description,
        ]);
        $assetType->save();

        return $assetType->refresh();
    }

    public function delete(TipoBem $assetType): void
    {
        if ($assetType->products()->exists()) {
            throw new RuntimeException('Este tipo de bem não pode ser excluído porque já está vinculado a produtos.');
        }

        $assetType->delete();
    }

    private function nextCode(): int
    {
        $query = TipoBem::query()
            ->select('codigo')
            ->orderByDesc('codigo');

        if (DB::connection()->getDriverName() !== 'sqlite') {
            $query->lockForUpdate();
        }

        $lastCode = $query->value('codigo');

        return ((int) $lastCode) + 1;
    }
}
