<?php

declare(strict_types=1);

namespace App\Support;

final class LegacyCpfValidator
{
    public static function isValid(string $cpf): bool
    {
        $digits = preg_replace('/\D/', '', $cpf);
        $digits = is_string($digits) ? $digits : '';

        if (strlen($digits) !== 11) {
            return false;
        }

        if (preg_match('/(\d)\1{10}/', $digits) === 1) {
            return false;
        }

        for ($target = 9; $target < 11; $target++) {
            $sum = 0;

            for ($index = 0; $index < $target; $index++) {
                $sum += ((int) $digits[$index]) * (($target + 1) - $index);
            }

            $digit = ((10 * $sum) % 11) % 10;

            if ((int) $digits[$target] !== $digit) {
                return false;
            }
        }

        return true;
    }
}
