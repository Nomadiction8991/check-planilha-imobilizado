<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Exception;

/**
 * ColumnMapping — Encapsula mapeamento de colunas CSV.
 * Centraliza lógica de conversão e validação de letras para índices.
 */
class ColumnMapping
{
    private const MAPEAMENTO_PADRAO = [
        'codigo' => 0,
        'complemento' => 3,
        'dependencia' => 15,
        'localidade' => 10,
    ];

    private const CAMPOS_PERMITIDOS = ['codigo', 'complemento', 'dependencia', 'localidade'];

    private array $mapeamento;

    public function __construct(array $mapeamento = [])
    {
        $this->mapeamento = $mapeamento ?: self::MAPEAMENTO_PADRAO;
    }

    /**
     * Parseia string de mapeamento em array estruturado.
     * Formato: "codigo=A;complemento=D;dependencia=P;localidade=K"
     */
    public static function fromString(string $mapeamentoStr): self
    {
        $mapeamento = self::MAPEAMENTO_PADRAO;

        if (empty($mapeamentoStr)) {
            return new self($mapeamento);
        }

        $pares = explode(';', $mapeamentoStr);
        foreach ($pares as $par) {
            $partes = explode('=', $par, 2);
            if (count($partes) !== 2) {
                continue;
            }

            $campo = trim(strtolower($partes[0]));
            $letra = trim(strtoupper($partes[1]));

            if (!in_array($campo, self::CAMPOS_PERMITIDOS, true)) {
                continue;
            }

            if (!self::validarLetra($letra)) {
                continue;
            }

            $indice = self::letraParaIndice($letra);
            if ($indice >= 0 && $indice < 256) {
                $mapeamento[$campo] = $indice;
            }
        }

        return new self($mapeamento);
    }

    /**
     * Valida se uma letra é válida para mapeamento.
     */
    private static function validarLetra(string $letra): bool
    {
        if (!preg_match('/^[A-Z]{1,2}$/', $letra)) {
            return false;
        }
        return strlen($letra) <= 2;
    }

    /**
     * Converte letra de coluna (A-Z, AA-ZZ) em índice numérico (0-701).
     */
    private static function letraParaIndice(string $letra): int
    {
        $letra = strtoupper($letra);

        if (!preg_match('/^[A-Z]+$/', $letra)) {
            return -1;
        }

        $indice = 0;
        for ($i = 0; $i < strlen($letra); $i++) {
            $indice = $indice * 26 + (ord($letra[$i]) - ord('A') + 1);
        }

        return $indice - 1;
    }

    /**
     * Obtém índice de uma coluna.
     */
    public function getIndice(string $campo): int
    {
        return $this->mapeamento[$campo] ?? self::MAPEAMENTO_PADRAO[$campo] ?? 0;
    }

    /**
     * Obtém todo o mapeamento.
     */
    public function getAll(): array
    {
        return $this->mapeamento;
    }

    /**
     * Define índice para um campo.
     */
    public function setIndice(string $campo, int $indice): void
    {
        if (in_array($campo, self::CAMPOS_PERMITIDOS, true) && $indice >= 0 && $indice < 256) {
            $this->mapeamento[$campo] = $indice;
        }
    }

    /**
     * Retorna representação em string.
     */
    public function toString(): string
    {
        $partes = [];
        foreach (self::CAMPOS_PERMITIDOS as $campo) {
            if (isset($this->mapeamento[$campo])) {
                $letra = self::indiceParaLetra($this->mapeamento[$campo]);
                $partes[] = "$campo=$letra";
            }
        }
        return implode(';', $partes);
    }

    /**
     * Converte índice numérico de volta em letra.
     */
    private static function indiceParaLetra(int $indice): string
    {
        $indice++;
        $letra = '';

        while ($indice > 0) {
            $indice--;
            $letra = chr(65 + ($indice % 26)) . $letra;
            $indice = intdiv($indice, 26);
        }

        return $letra;
    }
}
