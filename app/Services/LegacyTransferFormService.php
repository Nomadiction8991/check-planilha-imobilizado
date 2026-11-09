<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Legacy\Administracao;
use App\Models\Legacy\Comum;
use App\Models\Legacy\Dependencia;
use App\Models\Legacy\Transferencia;
use App\Models\Legacy\TransferenciaItem;
use RuntimeException;

/**
 * Monta as folhas dos formulários de saída (14.3) e de entrada (14.2) de uma transferência.
 */
final class LegacyTransferFormService
{
    private const FOLHA_SAIDA = 11;

    private const FOLHA_ENTRADA = 12;

    public function __construct(private readonly LegacyReportTemplateService $templates)
    {
    }

    /**
     * @return array{
     *   formulario: string,
     *   numero: int,
     *   paginas: list<array{numero: int, html: string}>,
     *   total_paginas: int,
     *   background_image_url: string,
     *   style_content: string
     * }
     */
    public function previa(Transferencia $transferencia, string $tipo): array
    {
        if (!in_array($tipo, ['saida', 'entrada'], true)) {
            throw new RuntimeException('Tipo de formulário inválido.');
        }

        $saida = $tipo === 'saida';
        $formulario = $saida ? '14.3' : '14.2';
        $porFolha = $saida ? self::FOLHA_SAIDA : self::FOLHA_ENTRADA;

        $origem = Comum::query()->find($transferencia->comum_origem_id);
        $destino = Comum::query()->find($transferencia->comum_destino_id);

        if ($origem === null || $destino === null) {
            throw new RuntimeException('Igreja da transferência não está mais disponível.');
        }

        $localDestino = (string) (Dependencia::query()
            ->whereKey($transferencia->dependencia_destino_id)
            ->value('descricao') ?? '');

        // O formulário sai em nome da igreja que o emite: a origem na saída, o destino na entrada.
        $planilha = $this->dadosDaIgreja($saida ? $origem : $destino);
        // O número de saída vai no campo n_relatorio do formulário de entrada, como referência à origem.
        $planilha['codigo'] = (string) $transferencia->numero_saida;
        $planilha['numero_entrada'] = (string) $transferencia->numero_entrada;
        $planilha['origem_administracao'] = $this->dadosDaIgreja($origem)['administracao'];
        $planilha['origem_cidade_setor'] = $this->cidadeESetor($origem);
        $planilha['origem_descricao'] = trim((string) $origem->descricao);

        $motivo = $saida
            ? 'Transferência para ' . $this->rotulo($destino)
            : 'Transferência de ' . $this->rotulo($origem);

        [$a4Block, $styleContent, $backgroundImageUrl] = $this->templates->loadTemplateParts($formulario);

        $folhas = $transferencia->itens()->orderBy('id')->get()->chunk($porFolha)->values();
        $paginas = [];

        foreach ($folhas as $indice => $itensDaFolha) {
            $produto = [
                'itens' => $itensDaFolha
                    ->map(fn (TransferenciaItem $item): array => $this->linhaDoItem($item, $localDestino))
                    ->values()
                    ->all(),
                'motivo' => $motivo,
                'data' => $transferencia->created_at?->format('d/m/Y') ?? '',
            ];

            $paginas[] = [
                'numero' => $indice + 1,
                'html' => $this->templates->renderFilledTemplate($formulario, $a4Block, $produto, $planilha),
            ];
        }

        return [
            'formulario' => $formulario,
            'numero' => $saida ? $transferencia->numero_saida : $transferencia->numero_entrada,
            'paginas' => $paginas,
            'total_paginas' => count($paginas),
            'background_image_url' => $backgroundImageUrl,
            'style_content' => $styleContent,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function dadosDaIgreja(Comum $igreja): array
    {
        $administracao = Administracao::query()->find($igreja->administracao_id);

        return [
            'descricao' => trim((string) $igreja->descricao),
            'comum' => $this->rotulo($igreja),
            'cidade' => trim((string) $igreja->cidade),
            'setor' => trim((string) $igreja->setor),
            'estado' => trim((string) $igreja->estado),
            'administracao' => trim((string) ($administracao?->descricao ?? '')),
            'administracao_cnpj' => trim((string) ($administracao?->cnpj ?? '')),
        ];
    }

    private function rotulo(Comum $igreja): string
    {
        $codigo = trim((string) $igreja->codigo);
        $descricao = trim((string) $igreja->descricao);

        return $codigo !== '' ? $codigo . ' - ' . $descricao : $descricao;
    }

    private function cidadeESetor(Comum $igreja): string
    {
        return implode(' / ', array_filter(
            [trim((string) $igreja->cidade), trim((string) $igreja->setor)],
            static fn (string $valor): bool => $valor !== '',
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function linhaDoItem(TransferenciaItem $item, string $local): array
    {
        return [
            'codigo' => (string) ($item->codigo_anterior ?? ''),
            'tipo' => (string) ($item->tipo_bem ?? ''),
            'descricao' => (string) ($item->descricao ?? ''),
            'quantidade' => (int) $item->quantidade,
            'valor' => $item->valor !== null ? number_format((float) $item->valor, 2, ',', '.') : '',
            'local' => $local,
        ];
    }
}
