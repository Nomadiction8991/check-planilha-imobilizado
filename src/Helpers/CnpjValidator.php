<?php

declare(strict_types=1);

namespace App\Helpers;

use InvalidArgumentException;

class CnpjValidator
{
    public static function validaCnpj(string $cnpj): string
    {
        $cnpj = self::normalize($cnpj);

        if (!ctype_digit($cnpj)) {
            self::validaAlfanumerico($cnpj);
            return $cnpj;
        }

        self::validaNumerico($cnpj);
        return $cnpj;
    }

    private static function normalize(string $cnpj): string
    {
        $cnpj = trim($cnpj);
        $cnpj = mb_strtoupper($cnpj, 'UTF-8');
        $cnpj = preg_replace('/[^0-9A-Z]/', '', $cnpj);

        if ($cnpj === '' || strlen($cnpj) !== 14) {
            throw new InvalidArgumentException('CNPJ deve conter exatamente 14 caracteres.');
        }

        return $cnpj;
    }

    private static function validaNumerico(string $cnpj): void
    {
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            throw new InvalidArgumentException('O CNPJ não pode conter todos os dígitos iguais.');
        }

        $numeros = substr($cnpj, 0, 12);
        $digitos = substr($cnpj, 12, 2);

        $peso = 5;
        $soma = 0;

        for ($i = 0; $i < 12; $i++) {
            $soma += (int)$numeros[$i] * $peso;
            $peso = ($peso === 2) ? 9 : $peso - 1;
        }

        $resto = $soma % 11;
        $d1 = ($resto < 2) ? 0 : 11 - $resto;

        if ((int)$digitos[0] !== $d1) {
            throw new InvalidArgumentException('Primeiro dígito verificador do CNPJ numérico é inválido.');
        }

        $numeros .= $d1;
        $peso = 6;
        $soma = 0;

        for ($i = 0; $i < 13; $i++) {
            $soma += (int)$numeros[$i] * $peso;
            $peso = ($peso === 2) ? 9 : $peso - 1;
        }

        $resto = $soma % 11;
        $d2 = ($resto < 2) ? 0 : 11 - $resto;

        if ((int)$digitos[1] !== $d2) {
            throw new InvalidArgumentException('Segundo dígito verificador do CNPJ numérico é inválido.');
        }
    }


    private static function validaAlfanumerico(string $cnpj): void
    {
        if (!preg_match('/^[0-9A-Z]{12}\d{2}$/', $cnpj)) {
            throw new InvalidArgumentException('Formato do CNPJ alfanumérico é inválido.');
        }
    }
}
