<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\SpreadsheetDiagnosticReport;
use App\Support\UniversalSpreadsheetReader;
use Exception;

final class SpreadsheetDiagnosticService
{
    /**
     * Diagnostica e valida uma planilha antes ou durante a importação.
     */
    public function diagnose(string $filePath): SpreadsheetDiagnosticReport
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return new SpreadsheetDiagnosticReport(
                filePath: $filePath,
                format: 'DESCONHECIDO',
                fileSizeBytes: 0,
                totalRows: 0,
                headerRowIndex: null,
                detectedColumns: [],
                validProductCount: 0,
                invalidRowCount: 0,
                churchCodes: [],
                warnings: [],
                errors: ["Arquivo não encontrado ou sem permissão de leitura: {$filePath}"],
                isImportReady: false,
            );
        }

        $fileSize = (int) filesize($filePath);
        $format = $this->detectFormat($filePath);

        $warnings = [];
        $errors = [];

        try {
            $rows = UniversalSpreadsheetReader::read($filePath);
        } catch (Exception $e) {
            return new SpreadsheetDiagnosticReport(
                filePath: $filePath,
                format: $format,
                fileSizeBytes: $fileSize,
                totalRows: 0,
                headerRowIndex: null,
                detectedColumns: [],
                validProductCount: 0,
                invalidRowCount: 0,
                churchCodes: [],
                warnings: [],
                errors: ["Erro ao decodificar arquivo ({$format}): " . $e->getMessage()],
                isImportReady: false,
            );
        }

        $totalRows = count($rows);
        if ($totalRows === 0) {
            return new SpreadsheetDiagnosticReport(
                filePath: $filePath,
                format: $format,
                fileSizeBytes: $fileSize,
                totalRows: 0,
                headerRowIndex: null,
                detectedColumns: [],
                validProductCount: 0,
                invalidRowCount: 0,
                churchCodes: [],
                warnings: ['A planilha está vazia.'],
                errors: ['Nenhum dado encontrado no arquivo.'],
                isImportReady: false,
            );
        }

        // Localizar linha de cabeçalho e mapear colunas
        $headerRowIndex = null;
        $detectedColumns = [];
        $sampleLimit = min($totalRows, 60);

        for ($i = 0; $i < $sampleLimit; $i++) {
            $row = $rows[$i];
            $normalizedRow = array_map(fn($cell) => $this->normalizeString((string) $cell), $row);

            $hasCodigo = false;
            $hasNome = false;

            foreach ($normalizedRow as $val) {
                if (str_contains($val, 'codigo')) {
                    $hasCodigo = true;
                }
                if (str_contains($val, 'nome') || str_contains($val, 'descricao')) {
                    $hasNome = true;
                }
            }

            if ($hasCodigo && $hasNome) {
                $headerRowIndex = $i;
                foreach ($normalizedRow as $colIdx => $val) {
                    if (str_contains($val, 'codigo') && !isset($detectedColumns['codigo'])) {
                        $detectedColumns['codigo'] = $colIdx;
                    } elseif ((str_contains($val, 'nome') || str_contains($val, 'descricao')) && !isset($detectedColumns['nome'])) {
                        $detectedColumns['nome'] = $colIdx;
                    } elseif (str_contains($val, 'localidade') && !isset($detectedColumns['localidade'])) {
                        $detectedColumns['localidade'] = $colIdx;
                    } elseif (str_contains($val, 'dependencia') && !isset($detectedColumns['dependencia'])) {
                        $detectedColumns['dependencia'] = $colIdx;
                    }
                }
                break;
            }
        }

        if ($headerRowIndex === null) {
            $warnings[] = 'Não foi possível detectar o cabeçalho automaticamente; usando mapeamento padrão (A=Código, D=Nome, K=Localidade, P=Dependência).';
            $detectedColumns = [
                'codigo' => 0,
                'nome' => 3,
                'localidade' => 10,
                'dependencia' => 15,
            ];
            $headerRowIndex = 0;
        }

        if (!isset($detectedColumns['codigo'])) {
            $errors[] = 'Coluna obrigatória de Código do Bem não identificada.';
        }
        if (!isset($detectedColumns['nome'])) {
            $errors[] = 'Coluna obrigatória de Nome/Descrição do Bem não identificada.';
        }

        $colCodigo = $detectedColumns['codigo'] ?? 0;
        $colNome = $detectedColumns['nome'] ?? 3;
        $startDataIndex = $headerRowIndex + 1;

        $validProductCount = 0;
        $invalidRowCount = 0;
        $churchCodesMap = [];

        for ($r = $startDataIndex; $r < $totalRows; $r++) {
            $row = $rows[$r];
            $codigo = trim((string) ($row[$colCodigo] ?? ''));

            if ($codigo === '') {
                continue;
            }

            $codigoLower = mb_strtolower($codigo, 'UTF-8');
            if (str_starts_with($codigoLower, 'total') || str_starts_with($codigoLower, 'subtotal')) {
                continue;
            }

            $nome = trim((string) ($row[$colNome] ?? ''));
            if ($nome === '') {
                $invalidRowCount++;
                continue;
            }

            $validProductCount++;

            // Extrair código da igreja/comum
            if (str_contains($codigo, ' / ')) {
                $parts = explode(' / ', $codigo, 2);
                $cCode = trim($parts[0]);
                if ($cCode !== '') {
                    $churchCodesMap[$cCode] = true;
                }
            }
        }

        $churchCodes = array_keys($churchCodesMap);
        sort($churchCodes);

        if ($validProductCount === 0) {
            $errors[] = 'Nenhum registro de bem patrimonial válido foi encontrado na planilha.';
        }

        if ($invalidRowCount > 0) {
            $warnings[] = sprintf('%d registro(s) com código preenchido mas sem nome/descrição foram ignorados.', $invalidRowCount);
        }

        $isImportReady = empty($errors) && $validProductCount > 0;

        return new SpreadsheetDiagnosticReport(
            filePath: $filePath,
            format: $format,
            fileSizeBytes: $fileSize,
            totalRows: $totalRows,
            headerRowIndex: $headerRowIndex,
            detectedColumns: $detectedColumns,
            validProductCount: $validProductCount,
            invalidRowCount: $invalidRowCount,
            churchCodes: $churchCodes,
            warnings: $warnings,
            errors: $errors,
            isImportReady: $isImportReady,
        );
    }

    private function detectFormat(string $filePath): string
    {
        $handle = @fopen($filePath, 'rb');
        if ($handle === false) {
            return 'DESCONHECIDO';
        }
        $magic = (string) fread($handle, 8);
        fclose($handle);

        if (str_starts_with($magic, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1")) {
            return 'XLS (Excel 97-2003 / BIFF8)';
        }

        if (str_starts_with($magic, "PK\x03\x04")) {
            return 'XLSX (Excel OpenXML)';
        }

        return 'CSV (Texto delimitado)';
    }

    private function normalizeString(string $val): string
    {
        $str = mb_strtolower(trim($val), 'UTF-8');
        $translit = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
        return $translit !== false ? $translit : $str;
    }
}
