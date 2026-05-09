<?php

declare(strict_types=1);

namespace App\Support;

use function data_get;

final class LegacyProductNameSupport
{
    public static function formatCurrentName(mixed $product): string
    {
        return self::formatName(
            self::firstNonEmpty($product, ['editado_bem', 'bem']),
            self::firstNonEmpty($product, ['editado_complemento', 'complemento']),
            self::firstNonEmpty($product, ['editado_altura_m', 'altura_m']),
            self::firstNonEmpty($product, ['editado_largura_m', 'largura_m']),
            self::firstNonEmpty($product, ['editado_comprimento_m', 'comprimento_m']),
        );
    }

    public static function formatHistoricalName(mixed $product, bool $useEdited): string
    {
        $prefix = $useEdited ? 'editado_' : '';

        return self::formatName(
            self::stringValue($product, $prefix . 'bem'),
            self::stringValue($product, $prefix . 'complemento'),
            self::stringValue($product, $prefix . 'altura_m'),
            self::stringValue($product, $prefix . 'largura_m'),
            self::stringValue($product, $prefix . 'comprimento_m'),
        );
    }

    public static function formatName(
        string $asset,
        string $complement = '',
        mixed $height = null,
        mixed $width = null,
        mixed $length = null,
    ): string {
        $parts = [];

        $asset = trim($asset);
        if ($asset !== '') {
            $parts[] = $asset;
        }

        $complement = trim($complement);
        if ($complement !== '') {
            $parts[] = $complement;
        }

        $dimensions = array_filter([
            self::formatDimension('A', $height),
            self::formatDimension('L', $width),
            self::formatDimension('C', $length),
        ]);

        if ($dimensions !== []) {
            $parts[] = implode(' ', $dimensions);
        }

        return trim(implode(' ', $parts));
    }

    private static function formatDimension(string $label, mixed $value): ?string
    {
        $normalized = self::normalizeNumericValue($value);

        if ($normalized === null) {
            return null;
        }

        return $label . '(' . $normalized . 'm)';
    }

    private static function normalizeNumericValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        if ($trimmed === '' || !is_numeric($trimmed)) {
            return null;
        }

        $number = (float) $trimmed;

        if ($number <= 0) {
            return null;
        }

        $formatted = number_format($number, 3, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        return $formatted === '' ? null : $formatted;
    }

    private static function firstNonEmpty(mixed $record, array $keys): string
    {
        foreach ($keys as $key) {
            $value = trim((string) data_get($record, $key, ''));

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private static function stringValue(mixed $record, string $key): string
    {
        return trim((string) data_get($record, $key, ''));
    }
}
