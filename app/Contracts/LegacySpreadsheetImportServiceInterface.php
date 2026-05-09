<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\SpreadsheetImportUploadData;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface LegacySpreadsheetImportServiceInterface
{
    public function responsibleUserOptions(): Collection;

    public function churchOptions(): Collection;

    public function administrationOptions(): Collection;

    public function uploadAndAnalyze(SpreadsheetImportUploadData $data, UploadedFile $file): int;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recentImports(?int $churchId, int $limit = 5): array;

    /**
     * @return array{
     *   importacao: array<string, mixed>,
     *   analise: array<string, mixed>,
     *   acoes_salvas: array<string, string>,
     *   igrejas_salvas: array<string, string>,
     *   status_por_comum: array<string, string>,
     *   igrejas_detectadas: array<int, array<string, mixed>>
     * }|null
     */
    public function loadPreview(int $importacaoId): ?array;

    /**
     * @param array<string, string> $acoes
     * @param array<string, string> $igrejas
     * @return array{total_salvas: int, igrejas_salvas: int}
     */
    public function savePreviewActions(int $importacaoId, array $acoes, array $igrejas): array;

    /**
     * @return array{acao: string, total_aplicadas: int}
     */
    public function applyBulkPreviewAction(int $importacaoId, string $acao): array;

    /**
     * @return array<string, mixed>
     */
    public function confirmImport(int $importacaoId, bool $importAll = true, array $acoes = [], array $igrejas = []): array;

    /**
     * @return array<string, mixed>|null
     */
    public function loadProgress(int $importacaoId): ?array;

    /**
     * @return array{
     *   modo: string,
     *   comum: array<string, mixed>|null,
     *   administracao: array<string, mixed>|null,
     *   importacao: array<string, mixed>|null,
     *   resumo: array{pendentes: int, resolvidos: int},
     *   erros: LengthAwarePaginator
     * }
     */
    public function loadImportErrors(?int $churchId, ?int $importacaoId, int $page = 1, int $perPage = 30): array;

    /**
     * @return array{filename: string, content: string}
     */
    public function downloadImportErrorsCsv(?int $churchId, ?int $importacaoId): array;

    /**
     * @return array{pendentes: int, resolvido: bool}
     */
    public function markImportErrorResolved(int $errorId, bool $resolved): array;
}
