<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LegacyNavigationServiceInterface;
use App\DTO\LegacyNavigationOrderData;
use Illuminate\Support\Facades\DB;
use JsonException;
use RuntimeException;

final class LegacyNavigationService implements LegacyNavigationServiceInterface
{
    private const DEFAULT_ORDER = [
        'products',
        'verification',
        'churches',
        'departments',
        'asset-types',
        'administrations',
        'users',
        'reports',
        'importacao',
        'audits',
        'configuracoes',
    ];

    /**
     * @return array<string, array{
     *     label: string,
     *     route: string,
     *     active_patterns: array<int, string>,
     *     permission: string|null,
     *     admin_only: bool
     * }>
     */
    private function catalog(): array
    {
        return [
            'products' => [
                'label' => 'Produtos',
                'route' => 'migration.products.index',
                'active_patterns' => ['migration.products.index', 'migration.products.create', 'migration.products.edit', 'migration.compat.products.*'],
                'permission' => 'products.view',
                'admin_only' => false,
            ],
            'verification' => [
                'label' => 'Verificação',
                'route' => 'migration.products.verification',
                'active_patterns' => ['migration.products.verification'],
                'permission' => 'products.edit',
                'admin_only' => false,
            ],
            'churches' => [
                'label' => 'Igrejas',
                'route' => 'migration.churches.index',
                'active_patterns' => ['migration.churches.*'],
                'permission' => 'churches.view',
                'admin_only' => false,
            ],
            'departments' => [
                'label' => 'Dependências',
                'route' => 'migration.departments.index',
                'active_patterns' => ['migration.departments.*'],
                'permission' => 'departments.view',
                'admin_only' => false,
            ],
            'asset-types' => [
                'label' => 'Tipos de bem',
                'route' => 'migration.asset-types.index',
                'active_patterns' => ['migration.asset-types.*'],
                'permission' => 'asset-types.view',
                'admin_only' => false,
            ],
            'administrations' => [
                'label' => 'Administrações',
                'route' => 'migration.administrations.index',
                'active_patterns' => ['migration.administrations.*'],
                'permission' => 'administrations.view',
                'admin_only' => false,
            ],
            'users' => [
                'label' => 'Usuários',
                'route' => 'migration.users.index',
                'active_patterns' => ['migration.users.*'],
                'permission' => 'users.view',
                'admin_only' => false,
            ],
            'reports' => [
                'label' => 'Relatórios',
                'route' => 'migration.reports.index',
                'active_patterns' => ['migration.reports.*'],
                'permission' => 'reports.view',
                'admin_only' => false,
            ],
            'importacao' => [
                'label' => 'Importação',
                'route' => 'migration.spreadsheets.create',
                'active_patterns' => ['migration.spreadsheets.*'],
                'permission' => 'spreadsheets.import',
                'admin_only' => false,
            ],
            'audits' => [
                'label' => 'Auditoria',
                'route' => 'migration.audits.index',
                'active_patterns' => ['migration.audits.*'],
                'permission' => 'audits.view',
                'admin_only' => false,
            ],
            'configuracoes' => [
                'label' => 'Configurações',
                'route' => 'migration.configuracoes.index',
                'active_patterns' => ['migration.configuracoes.*'],
                'permission' => null,
                'admin_only' => true,
            ],
        ];
    }

    public function currentOrder(): LegacyNavigationOrderData
    {
        $record = DB::table('configuracoes')->first();
        $storedOrder = $this->decodeStoredOrder(is_object($record) ? ($record->menu_order ?? null) : null);

        return new LegacyNavigationOrderData($this->normalizeOrder($storedOrder));
    }

    public function saveOrder(LegacyNavigationOrderData $data): void
    {
        $payload = [
            'menu_order' => $this->encodeOrder($this->normalizeOrder($data->items)),
        ];

        $existing = DB::table('configuracoes')->first();

        if ($existing === null) {
            DB::table('configuracoes')->insert($payload);
            return;
        }

        DB::table('configuracoes')->update($payload);
    }

    public function navigation(array $permissions, bool $isAdmin): array
    {
        $catalog = $this->catalog();
        $items = [];

        foreach ($this->currentOrder()->items as $key) {
            if (!isset($catalog[$key])) {
                continue;
            }

            $definition = $catalog[$key];
            if (!$isAdmin && $definition['admin_only']) {
                continue;
            }

            if (!$isAdmin && !$this->isGranted($permissions, $definition['permission'])) {
                continue;
            }

            $items[] = [
                'key' => $key,
                'label' => $definition['label'],
                'route' => route($definition['route']),
                'active_patterns' => $definition['active_patterns'],
            ];
        }

        return $items;
    }

    public function editorItems(): array
    {
        $catalog = $this->catalog();
        $items = [];

        foreach ($this->currentOrder()->items as $key) {
            if (!isset($catalog[$key])) {
                continue;
            }

            $definition = $catalog[$key];
            $items[] = [
                'key' => $key,
                'label' => $definition['label'],
                'subtitle' => $definition['admin_only']
                    ? 'Apenas administradores'
                    : ($definition['permission'] !== null ? 'Permissão: ' . $definition['permission'] : 'Disponível no menu'),
                'admin_only' => $definition['admin_only'],
            ];
        }

        return $items;
    }

    public function availableKeys(): array
    {
        return array_keys($this->catalog());
    }

    /**
     * @return array<int, string>
     */
    private function normalizeOrder(array $order): array
    {
        $availableKeys = $this->availableKeys();
        $normalized = [];

        foreach ($order as $key) {
            if (!is_string($key) || !in_array($key, $availableKeys, true) || in_array($key, $normalized, true)) {
                continue;
            }

            $normalized[] = $key;
        }

        foreach ($availableKeys as $key) {
            if (in_array($key, $normalized, true)) {
                continue;
            }

            $normalized[] = $key;
        }

        return $normalized;
    }

    /**
     * @return array<int, string>
     */
    private function decodeStoredOrder(mixed $storedOrder): array
    {
        if (!is_string($storedOrder) || trim($storedOrder) === '') {
            return [];
        }

        try {
            $decoded = json_decode($storedOrder, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, static fn (mixed $value): bool => is_string($value) && trim($value) !== ''));
    }

    private function encodeOrder(array $order): string
    {
        try {
            return json_encode(array_values($order), JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new RuntimeException('Não foi possível salvar a ordem do menu.');
        }
    }

    /**
     * @param array<string, bool> $permissions
     */
    private function isGranted(array $permissions, ?string $permission): bool
    {
        if ($permission === null) {
            return true;
        }

        return !empty($permissions[$permission]);
    }
}
