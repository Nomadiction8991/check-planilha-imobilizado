<?php

declare(strict_types=1);

namespace App\Services\Csv;

use League\Csv\Reader;
use League\Csv\CharsetConverter;
use Exception;

/**
 * CsvReader — Lê e normaliza um arquivo CSV do relatório de imobilizado CCB.
 *
 * Responsabilidades:
 *  - Detectar encoding (UTF-8, ISO-8859-1, Windows-1252)
 *  - Detectar delimitador (vírgula, ponto-vírgula, tabulação)
 *  - Ler linhas brutas usando league/csv
 *  - Localizar o início dos dados (após metadados e cabeçalho)
 */
class CsvReader
{
    private const AMOSTRA_BYTES = 8192;

    /**
     * Lê o CSV e retorna todas as linhas como array de arrays.
     * O índice de início de dados é determinado via encontrarInicioDados().
     *
     * @param string $caminho     Caminho do arquivo CSV
     * @param int    $puloLinhas  Linhas de metadados a pular (padrão da configuração)
     * @param array  $mapeamento  Mapeamento campo → índice de coluna
     * @return array{linhas: array, inicioLeitura: int} Linhas brutas e índice de início
     * @throws Exception Se o arquivo não puder ser lido
     */
    public function lerLinhasBrutas(string $caminho, int $puloLinhas, array $mapeamento): array
    {
        $amostra  = (string) file_get_contents($caminho, false, null, 0, self::AMOSTRA_BYTES);
        $encoding = mb_detect_encoding($amostra, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);

        $delimitador = $this->detectarDelimitador($amostra);

        $csv = Reader::createFromPath($caminho, 'r');
        $csv->setDelimiter($delimitador);
        $csv->setEnclosure('"');

        if ($encoding && $encoding !== 'UTF-8') {
            CharsetConverter::addTo($csv, $encoding, 'UTF-8');
        }

        $todasLinhas  = iterator_to_array($csv->getRecords(), false);
        $inicioLeitura = $this->encontrarInicioDados($todasLinhas, $puloLinhas, $mapeamento);

        return [
            'linhas'        => $todasLinhas,
            'inicioLeitura' => $inicioLeitura,
        ];
    }

    /**
     * Detecta o delimitador mais provável do CSV analisando uma amostra.
     */
    public function detectarDelimitador(string $amostra): string
    {
        $contVirgula      = substr_count($amostra, ',');
        $contPontoVirgula = substr_count($amostra, ';');
        $contTab          = substr_count($amostra, "\t");

        if ($contPontoVirgula > $contVirgula && $contPontoVirgula > $contTab) {
            return ';';
        }
        if ($contTab > $contVirgula && $contTab > $contPontoVirgula) {
            return "\t";
        }

        return ',';
    }

    /**
     * Encontra o índice da primeira linha de DADOS (logo após o cabeçalho).
     * Usa pulo_linhas como ponto de partida, mas tenta auto-detectar o cabeçalho
     * procurando por uma linha cujo primeiro campo contenha "Código" / "codigo".
     */
    public function encontrarInicioDados(array $linhas, int $puloLinhas, array $mapeamento): int
    {
        $totalLinhas = count($linhas);

        for ($i = 0; $i < min($totalLinhas, $puloLinhas + 10); $i++) {
            $primeiraCol     = trim((string) ($linhas[$i][0] ?? ''));
            $primeiraColNorm = $this->removerAcentos(strtolower($primeiraCol));

            if ($primeiraColNorm === 'codigo') {
                return $i + 1;
            }
        }

        $inicio = $puloLinhas + 1;
        return min($inicio, $totalLinhas);
    }

    // ─── Auxiliar ───

    private function removerAcentos(string $str): string
    {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
        return $converted !== false ? $converted : $str;
    }
}
