<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Collection;

final class LegacyProductTypeOptionSupport
{
    /**
     * @return array<int, string>
     */
    public static function optionsFromDescription(string $description): array
    {
        $parts = preg_split('/\s*\/\s*/', trim($description));
        $parts = is_array($parts) ? $parts : [];

        $options = array_values(array_filter(array_map(
            static fn (string $item): string => mb_strtoupper(trim($item), 'UTF-8'),
            $parts
        )));

        if ($options === []) {
            return [mb_strtoupper(trim($description), 'UTF-8')];
        }

        return array_values(array_unique($options));
    }

    /**
     * @param Collection<int, object> $assetTypes
     * @return array<int, array{code: string, description: string, options: array<int, string>}>
     */
    public static function buildMap(Collection $assetTypes): array
    {
        $map = [];

        foreach ($assetTypes as $assetType) {
            $assetTypeId = (int) data_get($assetType, 'id');

            $map[$assetTypeId] = [
                'code' => (string) data_get($assetType, 'codigo', ''),
                'description' => (string) data_get($assetType, 'descricao', ''),
                'options' => self::optionsFromDescription((string) data_get($assetType, 'descricao', '')),
            ];
        }

        return $map;
    }
}
