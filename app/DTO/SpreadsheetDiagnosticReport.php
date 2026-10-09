<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class SpreadsheetDiagnosticReport
{
    /**
     * @param array<string, int> $detectedColumns Mapeamento campo => índice de coluna
     * @param list<string> $churchCodes Lista de códigos de igrejas/comuns detectadas
     * @param list<string> $warnings Avisos identificados
     * @param list<string> $errors Erros identificados
     */
    public function __construct(
        public string $filePath,
        public string $format,
        public int $fileSizeBytes,
        public int $totalRows,
        public ?int $headerRowIndex,
        public array $detectedColumns,
        public int $validProductCount,
        public int $invalidRowCount,
        public array $churchCodes,
        public array $warnings,
        public array $errors,
        public bool $isImportReady,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'file_path' => $this->filePath,
            'format' => $this->format,
            'file_size_bytes' => $this->fileSizeBytes,
            'file_size_formatted' => $this->formatBytes($this->fileSizeBytes),
            'total_rows' => $this->totalRows,
            'header_row_index' => $this->headerRowIndex !== null ? $this->headerRowIndex + 1 : null,
            'detected_columns' => $this->detectedColumns,
            'valid_products_count' => $this->validProductCount,
            'invalid_rows_count' => $this->invalidRowCount,
            'church_codes' => $this->churchCodes,
            'warnings' => $this->warnings,
            'errors' => $this->errors,
            'is_import_ready' => $this->isImportReady,
        ];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return sprintf('%.2f MB', $bytes / 1048576);
        }
        if ($bytes >= 1024) {
            return sprintf('%.2f KB', $bytes / 1024);
        }
        return sprintf('%d B', $bytes);
    }
}
