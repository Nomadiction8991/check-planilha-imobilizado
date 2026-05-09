<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LegacyProductManagementServiceInterface;
use App\DTO\CreateLegacyProductData;
use App\DTO\UpdateLegacyProductData;
use App\Models\Legacy\Comum;
use App\Models\Legacy\Dependencia;
use App\Models\Legacy\Produto;
use App\Models\Legacy\TipoBem;
use App\Support\LegacyProductTypeOptionSupport;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LegacyProductManagementService implements LegacyProductManagementServiceInterface
{
    public function createMany(CreateLegacyProductData $data): int
    {
        $church = $this->resolveChurch($data->churchId);
        $assetType = $this->resolveAssetType($data->assetTypeId);
        $dependency = $this->resolveDependency($data->dependencyId, $church->id);
        $itemName = $this->normalizeItemName($assetType, $data->itemName);
        $complement = mb_strtoupper(trim($data->complement), 'UTF-8');
        $invoiceSupplier = $data->invoiceSupplier !== null
            ? mb_strtoupper(trim($data->invoiceSupplier), 'UTF-8')
            : null;

        DB::transaction(function () use ($data, $church, $assetType, $dependency, $itemName, $complement, $invoiceSupplier): void {
            for ($index = 0; $index < $data->multiplier; $index++) {
                Produto::query()->create([
                    'comum_id' => (int) $church->id,
                    'codigo' => $data->code !== null && trim($data->code) !== '' ? trim($data->code) : null,
                    'tipo_bem_id' => (int) $assetType->id,
                    'bem' => $itemName,
                    'complemento' => $complement,
                    'altura_m' => $data->heightMeters,
                    'largura_m' => $data->widthMeters,
                    'comprimento_m' => $data->lengthMeters,
                    'dependencia_id' => (int) $dependency->id,
                    'novo' => 1,
                    'importado' => 0,
                    'checado' => 0,
                    'editado' => 0,
                    'imprimir_etiqueta' => 0,
                    'imprimir_14_1' => $data->printReport141 ? 1 : 0,
                    'condicao_14_1' => $data->condition141,
                    'observacao' => '',
                    'nota_numero' => $data->invoiceNumber,
                    'nota_data' => $data->invoiceDate,
                    'nota_valor' => $data->invoiceValue,
                    'nota_fornecedor' => $invoiceSupplier,
                    'ativo' => 1,
                ]);
            }
        });

        return $data->multiplier;
    }

    public function update(Produto $product, UpdateLegacyProductData $data): Produto
    {
        $assetType = $this->resolveAssetType($data->editedAssetTypeId);
        $dependency = $this->resolveDependency($data->editedDependencyId, (int) $product->comum_id);
        $itemName = $this->normalizeItemName($assetType, $data->editedItemName);
        $invoiceSupplier = $data->invoiceSupplier !== null
            ? mb_strtoupper(trim($data->invoiceSupplier), 'UTF-8')
            : null;

        $product->fill([
            'editado_tipo_bem_id' => (int) $assetType->id,
            'editado_bem' => $itemName,
            'editado_complemento' => mb_strtoupper(trim($data->editedComplement), 'UTF-8'),
            'editado_altura_m' => $data->editedHeightMeters,
            'editado_largura_m' => $data->editedWidthMeters,
            'editado_comprimento_m' => $data->editedLengthMeters,
            'editado_dependencia_id' => (int) $dependency->id,
            'checado' => 1,
            'imprimir_etiqueta' => 1,
            'observacao' => mb_strtoupper(trim($data->observation), 'UTF-8'),
            'editado' => 1,
            'imprimir_14_1' => $data->printReport141 ? 1 : 0,
            'condicao_14_1' => $data->condition141,
            'nota_numero' => $data->invoiceNumber,
            'nota_data' => $data->invoiceDate,
            'nota_valor' => $data->invoiceValue,
            'nota_fornecedor' => $invoiceSupplier,
        ]);
        $product->save();

        return $product->refresh();
    }

    private function resolveChurch(int $churchId): Comum
    {
        $church = Comum::query()->find($churchId);

        if ($church === null) {
            throw new RuntimeException('A igreja selecionada não está mais disponível.');
        }

        return $church;
    }

    private function resolveAssetType(int $assetTypeId): TipoBem
    {
        $assetType = TipoBem::query()->find($assetTypeId);

        if ($assetType === null) {
            throw new RuntimeException('O tipo de bem selecionado não está mais disponível.');
        }

        return $assetType;
    }

    private function resolveDependency(int $dependencyId, int $churchId): Dependencia
    {
        $dependency = Dependencia::query()
            ->whereKey($dependencyId)
            ->where('comum_id', $churchId)
            ->first();

        if ($dependency === null) {
            throw new RuntimeException('A dependência selecionada não pertence à igreja informada.');
        }

        return $dependency;
    }

    private function normalizeItemName(TipoBem $assetType, string $itemName): string
    {
        $normalizedItemName = mb_strtoupper(trim($itemName), 'UTF-8');
        $options = LegacyProductTypeOptionSupport::optionsFromDescription((string) $assetType->descricao);

        if (!in_array($normalizedItemName, $options, true)) {
            throw new RuntimeException('O bem selecionado não é compatível com o tipo de bem informado.');
        }

        return $normalizedItemName;
    }
}
