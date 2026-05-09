<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\ProductVerificationItemData;
use App\Models\Legacy\Produto;

interface LegacyProductUtilityServiceInterface
{
    public function findForChurch(int $productId, int $churchId): ?Produto;

    /**
     * @return array{
     *     church: array{id:int, descricao:string},
     *     dependencies: list<array{id:int, descricao:string}>,
     *     products: list<array{codigo:string, dependencia:string}>,
     *     selected_dependency_id:?int,
     *     total_products:int,
     *     unique_codes:int,
     *     codes:string
     * }
     */
    public function labelCopyData(int $churchId, ?int $dependencyId): array;

    public function updateObservation(int $productId, int $churchId, string $observation): bool;

    public function updateCheck(int $productId, int $churchId, bool $checked): bool;

    public function updateLabel(int $productId, int $churchId, bool $printLabel): bool;

    /**
     * @param list<ProductVerificationItemData> $items
     */
    public function saveVerificationChecklist(int $churchId, array $items): int;

    public function signProducts(array $productIds, int $churchId, int $userId, string $action): int;

    public function clearEdits(int $productId, int $churchId): void;
}
