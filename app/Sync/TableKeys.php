<?php

declare(strict_types=1);

namespace App\Sync;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Descobre a chave primária de uma tabela e monta a consulta da linha a partir da chave
 * registrada no gatilho (valor simples, JSON para chave composta, "*" para tabela sem chave).
 */
final class TableKeys
{
    /**
     * @return list<string>
     */
    public static function primaryKey(string $tabela): array
    {
        foreach (Schema::getIndexes($tabela) as $indice) {
            if (($indice['primary'] ?? false) === true) {
                return array_values($indice['columns']);
            }
        }

        return [];
    }

    /**
     * @param  list<string>  $chavePrimaria
     */
    public static function query(string $tabela, array $chavePrimaria, string $chave): Builder
    {
        $consulta = DB::table($tabela);

        if ($chavePrimaria === [] || $chave === '*') {
            return $consulta;
        }

        if (count($chavePrimaria) === 1) {
            return $consulta->where($chavePrimaria[0], $chave);
        }

        $valores = json_decode($chave, true);
        $valores = is_array($valores) ? $valores : [];

        foreach ($chavePrimaria as $coluna) {
            $consulta->where($coluna, $valores[$coluna] ?? null);
        }

        return $consulta;
    }
}
