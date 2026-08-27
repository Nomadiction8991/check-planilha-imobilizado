<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\LegacyAuditEntryData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LegacyAuditTrailServiceInterface
{
    public function record(LegacyAuditEntryData $entry): void;

    /**
     * @param array<string, string> $filters
     * @param array<string, mixed> $query
     */
    public function paginate(
        array $filters,
        ?int $userId,
        ?int $administrationId,
        ?int $churchId,
        bool $isAdmin,
        string $path,
        array $query = [],
        int $page = 1,
        int $perPage = 20,
    ): LengthAwarePaginator;

    /**
     * @return array<int, string>
     */
    public function availableModules(): array;

    /**
     * Gera o conteúdo CSV de TODAS as entradas que casam com os filtros,
     * respeitando o escopo do usuário — sem limite de paginação.
     *
     * @param array<string, string> $filters
     * @return array{filename: string, content: string} content vazio sinaliza ausência de eventos
     */
    public function exportCsv(
        array $filters,
        ?int $userId,
        ?int $administrationId,
        ?int $churchId,
        bool $isAdmin,
    ): array;
}
