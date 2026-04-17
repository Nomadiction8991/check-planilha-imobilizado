<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LegacyProductUtilityServiceInterface;
use App\DTO\ProductVerificationItemData;
use App\Models\Legacy\Comum;
use App\Models\Legacy\Produto;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LegacyProductUtilityService implements LegacyProductUtilityServiceInterface
{
    public function findForChurch(int $productId, int $churchId): ?Produto
    {
        return Produto::query()
            ->with([
                'tipoBem:id,codigo,descricao',
                'dependencia:id,descricao',
                'comum:id,codigo,descricao',
            ])
            ->whereKey($productId)
            ->where('comum_id', $churchId)
            ->first();
    }

    public function labelCopyData(int $churchId, ?int $dependencyId): array
    {
        $church = Comum::query()
            ->whereKey($churchId)
            ->first(['id', 'descricao']);

        if ($church === null) {
            throw new RuntimeException('Igreja não encontrada.');
        }

        $dependencies = DB::table('produtos as p')
            ->leftJoin('dependencias as d_orig', 'p.dependencia_id', '=', 'd_orig.id')
            ->leftJoin('dependencias as d_edit', 'p.editado_dependencia_id', '=', 'd_edit.id')
            ->where('p.comum_id', $churchId)
            ->whereRaw('COALESCE(p.imprimir_etiqueta, 0) = 1')
            ->whereRaw('COALESCE(d_edit.descricao, d_orig.descricao) IS NOT NULL')
            ->distinct()
            ->orderByRaw('COALESCE(d_edit.descricao, d_orig.descricao)')
            ->get([
                DB::raw('COALESCE(p.editado_dependencia_id, p.dependencia_id) as id'),
                DB::raw('COALESCE(d_edit.descricao, d_orig.descricao) as descricao'),
            ])
            ->map(static fn (object $row): array => [
                'id' => (int) $row->id,
                'descricao' => (string) $row->descricao,
            ])
            ->values()
            ->all();

        $productsQuery = DB::table('produtos as p')
            ->leftJoin('dependencias as d_orig', 'p.dependencia_id', '=', 'd_orig.id')
            ->leftJoin('dependencias as d_edit', 'p.editado_dependencia_id', '=', 'd_edit.id')
            ->where('p.comum_id', $churchId)
            ->whereRaw('COALESCE(p.imprimir_etiqueta, 0) = 1');

        if ($dependencyId !== null) {
            $productsQuery->whereRaw('COALESCE(p.editado_dependencia_id, p.dependencia_id) = ?', [$dependencyId]);
        }

        $products = $productsQuery
            ->orderBy('p.codigo')
            ->get([
                'p.codigo',
                DB::raw("COALESCE(d_edit.descricao, d_orig.descricao, '') as dependencia"),
            ])
            ->map(static fn (object $row): array => [
                'codigo' => trim((string) $row->codigo),
                'dependencia' => (string) $row->dependencia,
            ])
            ->values()
            ->all();

        $codes = array_map(
            static fn (array $product): string => str_replace(' ', '', $product['codigo']),
            $products,
        );
        $codes = array_values(array_filter($codes, static fn (string $code): bool => $code !== ''));

        return [
            'church' => [
                'id' => (int) $church->id,
                'descricao' => (string) $church->descricao,
            ],
            'dependencies' => $dependencies,
            'products' => $products,
            'selected_dependency_id' => $dependencyId,
            'total_products' => count($products),
            'unique_codes' => count(array_unique($codes)),
            'codes' => implode(',', $codes),
        ];
    }

    public function updateObservation(int $productId, int $churchId, string $observation): bool
    {
        return Produto::query()
            ->whereKey($productId)
            ->where('comum_id', $churchId)
            ->update(['observacao' => mb_strtoupper(trim($observation), 'UTF-8')]) > 0;
    }

    public function updateCheck(int $productId, int $churchId, bool $checked): bool
    {
        return Produto::query()
            ->whereKey($productId)
            ->where('comum_id', $churchId)
            ->update(['checado' => $checked ? 1 : 0]) > 0;
    }

    public function updateLabel(int $productId, int $churchId, bool $printLabel): bool
    {
        return Produto::query()
            ->whereKey($productId)
            ->where('comum_id', $churchId)
            ->update(['imprimir_etiqueta' => $printLabel ? 1 : 0]) > 0;
    }

    /**
     * @param list<ProductVerificationItemData> $items
     */
    public function saveVerificationChecklist(int $churchId, array $items): int
    {
        $processed = 0;

        DB::transaction(function () use ($churchId, $items, &$processed): void {
            foreach ($items as $item) {
                if (!$item instanceof ProductVerificationItemData) {
                    continue;
                }

                $this->updateLabel($item->productId, $churchId, $item->printLabel);
                $this->updateCheck($item->productId, $churchId, $item->verified);
                $this->updateObservation($item->productId, $churchId, $item->observation);
                $processed++;
            }
        });

        return $processed;
    }

    public function signProducts(array $productIds, int $churchId, int $userId, string $action): int
    {
        if (!in_array($action, ['assinar', 'desassinar'], true)) {
            throw new RuntimeException('Ação inválida.');
        }

        $normalizedIds = array_values(array_filter(
            array_map(static fn (mixed $value): int => (int) $value, $productIds),
            static fn (int $id): bool => $id > 0,
        ));

        if ($normalizedIds === []) {
            throw new RuntimeException('Selecione ao menos um produto.');
        }

        return Produto::query()
            ->where('comum_id', $churchId)
            ->whereIn('id_produto', $normalizedIds)
            ->update([
                'administrador_acessor_id' => $action === 'assinar' ? $userId : null,
            ]);
    }

    public function clearEdits(int $productId, int $churchId): void
    {
        Produto::query()
            ->whereKey($productId)
            ->where('comum_id', $churchId)
            ->update([
                'editado_tipo_bem_id' => 0,
                'editado_bem' => '',
                'editado_complemento' => '',
                'editado_dependencia_id' => 0,
                'imprimir_etiqueta' => 0,
                'checado' => 0,
                'imprimir_14_1' => 0,
                'condicao_14_1' => '',
                'nota_numero' => null,
                'nota_data' => null,
                'nota_valor' => null,
                'nota_fornecedor' => '',
                'editado' => 0,
            ]);
    }
}
