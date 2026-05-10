<?php

declare(strict_types=1);

namespace App\Support;

use function data_get;

final class LegacyProductNameSupport
{
    public static function formatCurrentName(mixed $product): string
    {
        $useEditedValues = (int) data_get($product, 'editado', 0) === 1;

        return self::formatName(
            self::currentValue($product, $useEditedValues, 'editado_bem', 'bem'),
            self::currentValue($product, $useEditedValues, 'editado_complemento', 'complemento'),
            self::currentValue($product, $useEditedValues, 'editado_marca', 'marca'),
            self::currentValue($product, $useEditedValues, 'editado_altura_m', 'altura_m'),
            self::currentValue($product, $useEditedValues, 'editado_largura_m', 'largura_m'),
            self::currentValue($product, $useEditedValues, 'editado_comprimento_m', 'comprimento_m'),
        );
    }

    public static function formatHistoricalName(mixed $product, bool $useEdited): string
    {
        $prefix = $useEdited ? 'editado_' : '';

        return self::formatName(
            self::stringValue($product, $prefix . 'bem'),
            self::stringValue($product, $prefix . 'complemento'),
            self::stringValue($product, $prefix . 'marca'),
            self::stringValue($product, $prefix . 'altura_m'),
            self::stringValue($product, $prefix . 'largura_m'),
            self::stringValue($product, $prefix . 'comprimento_m'),
        );
    }

    public static function formatName(
        string $asset,
        string $complement = '',
        string $brand = '',
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

        $brand = trim($brand);
        if ($brand !== '') {
            $parts[] = $brand;
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

    private static function currentValue(mixed $record, bool $useEditedValues, string $editedKey, string $originalKey): string
    {
        if ($useEditedValues) {
            return trim((string) data_get($record, $editedKey, ''));
        }

        return self::firstNonEmpty($record, [$editedKey, $originalKey]);
    }

    private static function stringValue(mixed $record, string $key): string
    {
        return trim((string) data_get($record, $key, ''));
    }
}
