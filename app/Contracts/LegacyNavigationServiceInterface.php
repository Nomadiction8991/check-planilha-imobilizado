<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\LegacyNavigationOrderData;

interface LegacyNavigationServiceInterface
{
    public function currentOrder(): LegacyNavigationOrderData;

    public function saveOrder(LegacyNavigationOrderData $data): void;

    /**
     * @param array<string, bool> $permissions
     *
     * @return array<int, array{
     *     key: string,
     *     label: string,
     *     route: string,
     *     active_patterns: array<int, string>
     * }>
     */
    public function navigation(array $permissions, bool $isAdmin): array;

    /**
     * @return array<int, array{
     *     key: string,
     *     label: string,
     *     subtitle: string,
     *     admin_only: bool
     * }>
     */
    public function editorItems(): array;

    /**
     * @return array<int, string>
     */
    public function availableKeys(): array;
}
