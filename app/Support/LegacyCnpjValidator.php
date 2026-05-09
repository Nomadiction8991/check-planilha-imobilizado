<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

final class LegacyCnpjValidator
{
    public static function validate(string $cnpj): string
    {
        $cnpj = self::normalize($cnpj);

        if (!ctype_digit($cnpj)) {
            self::validateAlphaNumeric($cnpj);

            return $cnpj;
        }

        self::validateNumeric($cnpj);

        return $cnpj;
    }

    private static function normalize(string $cnpj): string
    {
        $cnpj = trim($cnpj);
        $cnpj = mb_strtoupper($cnpj, 'UTF-8');
        $cnpj = (string) preg_replace('/[^0-9A-Z]/', '', $cnpj);

        if ($cnpj === '' || strlen($cnpj) !== 14) {
            throw new InvalidArgumentException('CNPJ deve conter exatamente 14 caracteres.');
        }

        return $cnpj;
    }

    private static function validateNumeric(string $cnpj): void
    {
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            throw new InvalidArgumentException('O CNPJ não pode conter todos os dígitos iguais.');
        }

        $numbers = substr($cnpj, 0, 12);
        $digits = substr($cnpj, 12, 2);
        $weight = 5;
        $sum = 0;

        for ($index = 0; $index < 12; $index++) {
            $sum += (int) $numbers[$index] * $weight;
            $weight = $weight === 2 ? 9 : $weight - 1;
        }

        $rest = $sum % 11;
        $digitOne = $rest < 2 ? 0 : 11 - $rest;

        if ((int) $digits[0] !== $digitOne) {
            throw new InvalidArgumentException('Primeiro dígito verificador do CNPJ numérico é inválido.');
        }

        $numbers .= (string) $digitOne;
        $weight = 6;
        $sum = 0;

        for ($index = 0; $index < 13; $index++) {
            $sum += (int) $numbers[$index] * $weight;
            $weight = $weight === 2 ? 9 : $weight - 1;
        }

        $rest = $sum % 11;
        $digitTwo = $rest < 2 ? 0 : 11 - $rest;

        if ((int) $digits[1] !== $digitTwo) {
            throw new InvalidArgumentException('Segundo dígito verificador do CNPJ numérico é inválido.');
        }
    }

    private static function validateAlphaNumeric(string $cnpj): void
    {
        if (!preg_match('/^[0-9A-Z]{12}\d{2}$/', $cnpj)) {
            throw new InvalidArgumentException('Formato do CNPJ alfanumérico é inválido.');
        }
    }
}
