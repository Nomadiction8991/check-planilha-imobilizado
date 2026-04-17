<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Support\Collection;

interface LegacyReportServiceInterface
{
    public function churchOptions(): Collection;

    /**
     * @return array<int, array{codigo: string, titulo: string, descricao: string, rota: string, quantidade: int}>
     */
    public function listAvailableReports(int $churchId): array;

    /**
     * @return array{
     *   formulario: string,
     *   planilha: array<string, mixed>,
     *   paginas: array<int, array{numero: int, html: string}>,
     *   total_paginas: int,
     *   background_image_url: string,
     *   style_content: string
     * }
     */
    public function buildReportPreview(int $churchId, string $formulario): array;

    /**
     * @param array{
     *   mostrar_pendentes?: bool,
     *   mostrar_checados?: bool,
     *   mostrar_observacao?: bool,
     *   mostrar_checados_observacao?: bool,
     *   mostrar_etiqueta?: bool,
     *   mostrar_alteracoes?: bool,
     *   mostrar_novos?: bool,
     *   dependencia?: int|null
     * } $filters
     *
     * @return array{
     *   planilha: array<string, mixed>,
     *   filtros: array<string, bool|int|null>,
     *   dependencias: array<int, array{id: int, descricao: string}>,
     *   secoes: array<string, array{titulo: string, itens: array<int, array<string, mixed>>, total: int}>,
     *   resumo: array{
     *     total_geral: int,
     *     total_pendentes: int,
     *     total_checados: int,
     *     total_observacao: int,
     *     total_checados_observacao: int,
     *     total_etiqueta: int,
     *     total_alteracoes: int,
     *     total_novos: int,
     *     total_mostrar: int
     *   }
     * }
     */
    public function buildChangeHistory(int $churchId, array $filters): array;
}
