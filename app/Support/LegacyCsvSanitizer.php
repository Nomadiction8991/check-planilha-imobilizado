<?php

declare(strict_types=1);

namespace App\Support;

final class LegacyCsvSanitizer
{
    public static function sanitizeText(?string $value): string
    {
        $value ??= '';

        if ($value !== '' && str_contains("=+-@\t\r", $value[0])) {
            return "'" . $value;
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $row
     * @return array<array-key, mixed>
     */
    public static function sanitizeRow(array $row): array
    {
        $sanitized = [];

        foreach ($row as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = self::sanitizeText($value);
            } elseif ($value === null) {
                $sanitized[$key] = '';
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
