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
     * @return array{
     *   planilha: array<string, mixed>,
     *   itens: array<int, array<string, mixed>>,
     *   resumo: array{
     *     total_geral: int,
     *     total_pendentes: int,
     *     total_checados: int,
     *     total_observacao: int,
     *     total_etiqueta: int,
     *     total_alteracoes: int,
     *     total_novos: int,
     *     total_checados_observacao: int,
     *     total_checados_etiqueta: int,
     *     total_observacao_etiqueta: int,
     *     total_checados_observacao_etiqueta: int,
     *     total_editados_checados: int,
     *     total_editados_observacao: int,
     *     total_editados_etiqueta: int,
     *     total_editados_checados_etiqueta: int,
     *     total_editados_observacao_etiqueta: int,
     *     total_editados_checados_observacao: int,
     *     total_editados_checados_observacao_etiqueta: int,
     *     total_backup: int
     *   },
     *   backup: array{filename: string, content: string}
     * }
     */
    public function buildVerificationPositionReport(int $churchId): array;

    /**
     * @return array{filename: string, content: string}
     */
    public function downloadVerificationPositionCsv(int $churchId): array;
}
