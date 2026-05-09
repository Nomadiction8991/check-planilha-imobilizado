<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LegacyAuthSessionServiceInterface;
use App\Contracts\LegacyReportServiceInterface;
use App\Models\Legacy\Comum;
use App\Support\LegacyProductNameSupport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use RuntimeException;

class LegacyReportService implements LegacyReportServiceInterface
{
    private const REPORTS = [
        '14.1' => [
            'titulo' => 'Relatório 14.1',
            'descricao' => 'Formulário de doação e aquisição de bens',
        ],
        '14.2' => [
            'titulo' => 'Relatório 14.2',
            'descricao' => 'Formulário de alteração de bem',
        ],
        '14.3' => [
            'titulo' => 'Relatório 14.3',
            'descricao' => 'Formulário de exclusão de bem',
        ],
        '14.4' => [
            'titulo' => 'Relatório 14.4',
            'descricao' => 'Formulário de transferência de bem',
        ],
        '14.5' => [
            'titulo' => 'Relatório 14.5',
            'descricao' => 'Formulário de manutenção de bem',
        ],
        '14.6' => [
            'titulo' => 'Relatório 14.6',
            'descricao' => 'Formulário de reparo de bem',
        ],
        '14.7' => [
            'titulo' => 'Relatório 14.7',
            'descricao' => 'Formulário de empréstimo/manutenção/troca',
        ],
        '14.8' => [
            'titulo' => 'Relatório 14.8',
            'descricao' => 'Formulário de inventário da casa de oração',
        ],
    ];

    public function __construct(
        private readonly LegacyReportTemplateService $templates,
        private readonly LegacyAuthSessionServiceInterface $auth,
    ) {
    }

    public function churchOptions(): Collection
    {
        return Comum::query()
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'descricao']);
    }

    public function listAvailableReports(int $churchId): array
    {
        $reports = [];
        $permissions = (array) Session::get('legacy_permissions', []);

        foreach (self::REPORTS as $codigo => $definition) {
            if (!in_array($codigo, ['14.1', '14.6'], true)) {
                $reports[] = [
                    'codigo' => $codigo,
                    'titulo' => $definition['titulo'],
                    'descricao' => $definition['descricao'] . ' (folha em branco)',
                    'rota' => route('migration.reports.show', ['formulario' => $codigo, 'comum_id' => $churchId]),
                    'quantidade' => 1,
                ];

                continue;
            }

            $products = $this->loadProductsByForm($churchId, $codigo);
            $quantity = $codigo === '14.6'
                ? array_sum(array_map(static fn (array $page): int => count($page['itens'] ?? []), $products))
                : count($products);

            if ($quantity === 0) {
                continue;
            }

            $reports[] = [
                'codigo' => $codigo,
                'titulo' => $definition['titulo'],
                'descricao' => $definition['descricao'],
                'rota' => route('migration.reports.show', ['formulario' => $codigo, 'comum_id' => $churchId]),
                'quantidade' => $quantity,
            ];
        }

        if (!empty($permissions['reports.changes.view'] ?? false)) {
            $positionRows = $this->loadVerificationPositionProducts($churchId);

            $reports[] = [
                'codigo' => 'POS',
                'titulo' => 'Posição de estoque',
                'descricao' => 'Backup da posição de verificação e dos itens conferidos',
                'rota' => route('migration.reports.changes', ['comum_id' => $churchId]),
                'quantidade' => count($positionRows),
            ];
        }

        return $reports;
    }

    public function buildReportPreview(int $churchId, string $formulario): array
    {
        $normalizedForm = str_replace('-', '.', trim($formulario));

        if (!array_key_exists($normalizedForm, self::REPORTS)) {
            throw new RuntimeException('Formulário inválido.');
        }

        $churchData = $this->loadChurchData($churchId);

        if ($churchData === []) {
            throw new RuntimeException('Igreja não encontrada para gerar o relatório.');
        }

        $requiresData = in_array($normalizedForm, ['14.1', '14.6'], true);
        $products = $requiresData ? $this->loadProductsByForm($churchId, $normalizedForm) : [[]];

        if ($requiresData && $products === []) {
            throw new RuntimeException('O formulário selecionado não está disponível para esta igreja.');
        }

        [$a4Block, $styleContent, $backgroundImageUrl] = $this->templates->loadTemplateParts($normalizedForm);

        $pages = [];

        foreach ($products as $index => $product) {
            $html = $this->templates->renderFilledTemplate($normalizedForm, $a4Block, $product, $churchData);
            $pages[] = [
                'numero' => $index + 1,
                'html' => $html,
            ];
        }

        return [
            'formulario' => $normalizedForm,
            'planilha' => $churchData,
            'paginas' => $pages,
            'total_paginas' => count($pages),
            'background_image_url' => $backgroundImageUrl,
            'style_content' => $styleContent,
        ];
    }

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
     *     total_editados_checados_observacao: int,
     *     total_backup: int
     *   },
     *   backup: array{filename: string, content: string}
     * }
     */
    public function buildVerificationPositionReport(int $churchId): array
    {
        $churchData = $this->loadChurchData($churchId);

        if ($churchData === []) {
            throw new RuntimeException('Igreja não encontrada para abrir a posição de estoque.');
        }

        $rawProducts = $this->loadVerificationPositionProducts($churchId);
        $items = [];

        $summary = [
            'total_geral' => 0,
            'total_pendentes' => 0,
            'total_checados' => 0,
            'total_observacao' => 0,
            'total_etiqueta' => 0,
            'total_alteracoes' => 0,
            'total_novos' => 0,
            'total_checados_observacao' => 0,
            'total_checados_etiqueta' => 0,
            'total_observacao_etiqueta' => 0,
            'total_checados_observacao_etiqueta' => 0,
            'total_editados_checados' => 0,
            'total_editados_observacao' => 0,
            'total_editados_etiqueta' => 0,
            'total_editados_checados_etiqueta' => 0,
            'total_editados_observacao_etiqueta' => 0,
            'total_editados_checados_observacao' => 0,
            'total_editados_checados_observacao_etiqueta' => 0,
            'total_backup' => 0,
        ];

        foreach ($rawProducts as $rawProduct) {
            $item = $this->hydrateVerificationPositionItem($rawProduct);
            $items[] = $item;

            $summary['total_geral']++;

            if ($item['pendente'] === true) {
                $summary['total_pendentes']++;
            }

            if ($item['checado'] === true) {
                $summary['total_checados']++;
            }

            if ($item['observacoes'] !== '') {
                $summary['total_observacao']++;
            }

            if ($item['imprimir_etiqueta'] === true) {
                $summary['total_etiqueta']++;
            }

            if ($item['editado'] === true) {
                $summary['total_alteracoes']++;
            }

            if ($item['novo'] === true) {
                $summary['total_novos']++;
            }

            if ($item['checado'] === true && $item['observacoes'] !== '') {
                $summary['total_checados_observacao']++;
            }

            if ($item['checado'] === true && $item['imprimir_etiqueta'] === true) {
                $summary['total_checados_etiqueta']++;
            }

            if ($item['observacoes'] !== '' && $item['imprimir_etiqueta'] === true) {
                $summary['total_observacao_etiqueta']++;
            }

            if ($item['checado'] === true && $item['observacoes'] !== '' && $item['imprimir_etiqueta'] === true) {
                $summary['total_checados_observacao_etiqueta']++;
            }

            if ($item['editado'] === true && $item['checado'] === true) {
                $summary['total_editados_checados']++;
            }

            if ($item['editado'] === true && $item['observacoes'] !== '') {
                $summary['total_editados_observacao']++;
            }

            if ($item['editado'] === true && $item['imprimir_etiqueta'] === true) {
                $summary['total_editados_etiqueta']++;
            }

            if ($item['editado'] === true && $item['checado'] === true && $item['imprimir_etiqueta'] === true) {
                $summary['total_editados_checados_etiqueta']++;
            }

            if ($item['editado'] === true && $item['observacoes'] !== '' && $item['imprimir_etiqueta'] === true) {
                $summary['total_editados_observacao_etiqueta']++;
            }

            if ($item['editado'] === true && $item['checado'] === true && $item['observacoes'] !== '') {
                $summary['total_editados_checados_observacao']++;
            }

            if ($item['editado'] === true && $item['checado'] === true && $item['observacoes'] !== '' && $item['imprimir_etiqueta'] === true) {
                $summary['total_editados_checados_observacao_etiqueta']++;
            }
        }

        $summary['total_backup'] = count($items);

        return [
            'planilha' => $churchData,
            'itens' => $items,
            'resumo' => $summary,
            'backup' => $this->buildVerificationPositionCsv($churchData, $items),
        ];
    }

    /**
     * @return array{filename: string, content: string}
     */
    public function downloadVerificationPositionCsv(int $churchId): array
    {
        $report = $this->buildVerificationPositionReport($churchId);

        return $report['backup'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadVerificationPositionProducts(int $churchId): array
    {
        return DB::table('produtos as p')
            ->leftJoin('tipos_bens as tb', 'p.tipo_bem_id', '=', 'tb.id')
            ->leftJoin('tipos_bens as etb', 'p.editado_tipo_bem_id', '=', 'etb.id')
            ->leftJoin('dependencias as d_orig', 'p.dependencia_id', '=', 'd_orig.id')
            ->leftJoin('dependencias as d_edit', 'p.editado_dependencia_id', '=', 'd_edit.id')
            ->where('p.comum_id', $churchId)
            ->where('p.ativo', 1)
            ->orderBy('p.codigo')
            ->get([
                'p.id_produto as id',
                'p.codigo',
                DB::raw('CAST(p.checado AS SIGNED) as checado'),
                DB::raw('CAST(p.imprimir_etiqueta AS SIGNED) as imprimir'),
                DB::raw('CAST(p.editado AS SIGNED) as editado'),
                DB::raw('CAST(p.novo AS SIGNED) as novo'),
                'p.observacao as observacoes',
                'p.bem',
                'p.complemento',
                'p.editado_marca',
                'p.altura_m',
                'p.largura_m',
                'p.comprimento_m',
                'p.editado_bem',
                'p.editado_complemento',
                'p.editado_altura_m',
                'p.editado_largura_m',
                'p.editado_comprimento_m',
                DB::raw("NULLIF(CONCAT_WS(' ', p.editado_bem, p.editado_complemento, p.editado_marca), '') as nome_editado"),
                'tb.codigo as tipo_codigo',
                'tb.descricao as tipo_desc',
                'etb.codigo as editado_tipo_codigo',
                'etb.descricao as editado_tipo_desc',
                'd_orig.descricao as dependencia_desc',
                'd_edit.descricao as editado_dependencia_desc',
            ])
            ->map(function ($item): array {
                $product = (array) $item;
                $product['nome_original'] = $this->buildChangeHistoryTitle($product, false);
                $product['nome_atual'] = ((int) ($product['editado'] ?? 0) === 1 || trim((string) ($product['nome_editado'] ?? '')) !== '')
                    ? $this->buildChangeHistoryTitle($product, true)
                    : $product['nome_original'];

                return $product;
            })
            ->all();
    }

    /**
     * @param array<string, mixed> $product
     * @return array<string, mixed>
     */
    private function hydrateVerificationPositionItem(array $product): array
    {
        $observacoes = trim((string) ($product['observacoes'] ?? ''));
        $checado = (int) ($product['checado'] ?? 0) === 1;
        $imprimirEtiqueta = (int) ($product['imprimir'] ?? 0) === 1;
        $editado = (int) ($product['editado'] ?? 0) === 1;
        $novo = (int) ($product['novo'] ?? 0) === 1;
        [$statusKey, $statusLabel] = $this->resolveVerificationPositionStatus($checado, $observacoes !== '', $imprimirEtiqueta, $editado, $novo);

        return [
            'id' => (int) ($product['id'] ?? 0),
            'codigo' => (string) ($product['codigo'] ?? ''),
            'nome_original' => (string) ($product['nome_original'] ?? ''),
            'nome_atual' => (string) ($product['nome_atual'] ?? ''),
            'dependencia' => trim((string) ($product['dependencia_desc'] ?? $product['editado_dependencia_desc'] ?? '')),
            'observacoes' => $observacoes,
            'checado' => $checado,
            'imprimir_etiqueta' => $imprimirEtiqueta,
            'editado' => $editado,
            'novo' => $novo,
            'pendente' => !$checado && !$imprimirEtiqueta && $observacoes === '' && !$editado && !$novo,
            'status_key' => $statusKey,
            'status_label' => $statusLabel,
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveVerificationPositionStatus(
        bool $checado,
        bool $temObservacao,
        bool $imprimirEtiqueta,
        bool $editado,
        bool $novo,
    ): array {
        return match (true) {
            $editado && $checado && $temObservacao && $imprimirEtiqueta => ['editado_checado_observacao_etiqueta', 'Editado, checado, observação e etiqueta'],
            $editado && $checado && $temObservacao => ['editado_checado_observacao', 'Editado, checado e observação'],
            $editado && $checado && $imprimirEtiqueta => ['editado_checado_etiqueta', 'Editado e checado para etiqueta'],
            $editado && $temObservacao && $imprimirEtiqueta => ['editado_observacao_etiqueta', 'Editado com observação para etiqueta'],
            $editado && $checado => ['editado_checado', 'Editado e checado'],
            $editado && $temObservacao => ['editado_observacao', 'Editado com observação'],
            $editado && $imprimirEtiqueta => ['editado_etiqueta', 'Editado para etiqueta'],
            $editado => ['editado', 'Editado'],
            $checado && $temObservacao && $imprimirEtiqueta => ['checado_observacao_etiqueta', 'Checado com observação para etiqueta'],
            $checado && $temObservacao => ['checado_observacao', 'Checado com observação'],
            $checado && $imprimirEtiqueta => ['checado_etiqueta', 'Checado para etiqueta'],
            $temObservacao && $imprimirEtiqueta => ['observacao_etiqueta', 'Com observação para etiqueta'],
            $checado => ['checado', 'Checado'],
            $temObservacao => ['observacao', 'Com observação'],
            $imprimirEtiqueta => ['etiqueta', 'Para etiquetas'],
            $novo => ['novo', 'Novo'],
            default => ['pendente', 'Pendente'],
        };
    }

    /**
     * @param array<string, mixed> $churchData
     * @param array<int, array<string, mixed>> $items
     * @return array{filename: string, content: string}
     */
    private function buildVerificationPositionCsv(array $churchData, array $items): array
    {
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            throw new RuntimeException('Não foi possível preparar o backup da posição.');
        }

        fwrite($stream, "\xEF\xBB\xBF");

        fputcsv($stream, [
            'Código',
            'Situação',
            'Descrição original',
            'Descrição atual',
            'Dependência',
            'Checado',
            'Etiqueta',
            'Observação',
            'Editado',
            'Novo',
        ], ';');

        foreach ($items as $item) {
            fputcsv($stream, [
                (string) ($item['codigo'] ?? ''),
                (string) ($item['status_label'] ?? ''),
                (string) ($item['nome_original'] ?? ''),
                (string) ($item['nome_atual'] ?? ''),
                (string) ($item['dependencia'] ?? ''),
                ($item['checado'] ?? false) === true ? '1' : '0',
                ($item['imprimir_etiqueta'] ?? false) === true ? '1' : '0',
                (string) ($item['observacoes'] ?? ''),
                ($item['editado'] ?? false) === true ? '1' : '0',
                ($item['novo'] ?? false) === true ? '1' : '0',
            ], ';');
        }

        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        $churchCode = $this->formatShortCode((string) ($churchData['codigo'] ?? ''));

        return [
            'filename' => 'posicao_verificacao'
                . ($churchCode !== '' ? '_' . $churchCode : '')
                . '_' . date('Ymd_His') . '.csv',
            'content' => $content !== false ? $content : '',
        ];
    }

    public function buildChangeHistory(int $churchId, array $filters): array
    {
        $churchData = $this->loadChurchData($churchId);

        if ($churchData === []) {
            throw new RuntimeException('Igreja não encontrada para abrir a posição de estoque.');
        }

        $normalizedFilters = [
            'mostrar_pendentes' => (bool) ($filters['mostrar_pendentes'] ?? false),
            'mostrar_checados' => (bool) ($filters['mostrar_checados'] ?? false),
            'mostrar_observacao' => (bool) ($filters['mostrar_observacao'] ?? false),
            'mostrar_checados_observacao' => (bool) ($filters['mostrar_checados_observacao'] ?? false),
            'mostrar_etiqueta' => (bool) ($filters['mostrar_etiqueta'] ?? false),
            'mostrar_checados_etiqueta' => (bool) ($filters['mostrar_checados_etiqueta'] ?? false),
            'mostrar_observacao_etiqueta' => (bool) ($filters['mostrar_observacao_etiqueta'] ?? false),
            'mostrar_checados_observacao_etiqueta' => (bool) ($filters['mostrar_checados_observacao_etiqueta'] ?? false),
            'mostrar_alteracoes' => (bool) ($filters['mostrar_alteracoes'] ?? false),
            'mostrar_novos' => (bool) ($filters['mostrar_novos'] ?? false),
            'dependencia' => isset($filters['dependencia']) && (int) $filters['dependencia'] > 0
                ? (int) $filters['dependencia']
                : null,
        ];

        $products = $this->loadChangeHistoryProducts($churchId, $normalizedFilters['dependencia']);
        $sections = $this->classifyChangeHistoryProducts($products);
        $totals = [
            'total_geral' => count($products),
            'total_pendentes' => count($sections['pendentes']['itens']),
            'total_checados' => count($sections['checados']['itens']),
            'total_observacao' => count($sections['observacao']['itens']),
            'total_checados_observacao' => count($sections['checados_observacao']['itens']),
            'total_etiqueta' => count($sections['etiqueta']['itens']),
            'total_checados_etiqueta' => count($sections['checados_etiqueta']['itens']),
            'total_observacao_etiqueta' => count($sections['observacao_etiqueta']['itens']),
            'total_checados_observacao_etiqueta' => count($sections['checados_observacao_etiqueta']['itens']),
            'total_alteracoes' => count($sections['alteracoes']['itens']),
            'total_novos' => count($sections['novos']['itens']),
            'total_mostrar' => 0,
        ];

        $selectedSections = [
            'mostrar_pendentes' => 'pendentes',
            'mostrar_checados' => 'checados',
            'mostrar_observacao' => 'observacao',
            'mostrar_checados_observacao' => 'checados_observacao',
            'mostrar_etiqueta' => 'etiqueta',
            'mostrar_checados_etiqueta' => 'checados_etiqueta',
            'mostrar_observacao_etiqueta' => 'observacao_etiqueta',
            'mostrar_checados_observacao_etiqueta' => 'checados_observacao_etiqueta',
            'mostrar_alteracoes' => 'alteracoes',
            'mostrar_novos' => 'novos',
        ];

        $selectedItemIds = [];
        foreach ($selectedSections as $flag => $sectionKey) {
            if ($normalizedFilters[$flag] !== true) {
                continue;
            }

            foreach ($sections[$sectionKey]['itens'] as $item) {
                $itemId = (int) ($item['id'] ?? 0);

                if ($itemId > 0) {
                    $selectedItemIds[$itemId] = true;
                }
            }
        }

        $totals['total_mostrar'] = count($selectedItemIds);

        return [
            'planilha' => $churchData,
            'filtros' => $normalizedFilters,
            'dependencias' => $this->loadChangeHistoryDependencies($churchId),
            'secoes' => $sections,
            'resumo' => $totals,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function loadChurchData(int $churchId): array
    {
        $result = DB::table('comums as c')
            ->leftJoin('administracoes as a', 'c.administracao_id', '=', 'a.id')
            ->select([
                'c.codigo',
                'c.cnpj',
            'c.descricao',
            DB::raw('COALESCE(TRIM(a.descricao), "") AS administracao'),
            DB::raw('COALESCE(TRIM(a.descricao), "") AS administracao_descricao'),
            DB::raw('COALESCE(TRIM(a.cnpj), "") AS administracao_cnpj'),
            'c.cidade',
            'c.setor',
            'c.estado',
            'c.estado_administracao',
            'c.cidade_administracao',
        ])
            ->where('c.id', $churchId)
            ->first();

        if ($result === null) {
            return [];
        }

        return array_merge((array) $result, [
            'usuario_nome_relatorio' => $this->resolveCurrentUserName(),
        ]);
    }

    private function resolveCurrentUserName(): string
    {
        $currentUser = $this->auth->currentUser();

        if ($currentUser !== null) {
            $name = trim((string) ($currentUser['nome'] ?? ''));

            if ($name !== '') {
                return $name;
            }
        }

        return trim((string) Session::get('usuario_nome', ''));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadProductsByForm(int $churchId, string $formulario): array
    {
        return match ($formulario) {
            '14.1' => $this->loadProducts141($churchId),
            '14.2', '14.3', '14.4', '14.5', '14.6', '14.7', '14.8' => $formulario === '14.6'
                ? $this->loadProducts146($churchId)
                : [[]],
            default => [],
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadProducts141(int $churchId): array
    {
        return DB::table('produtos as p')
            ->leftJoin('tipos_bens as tb', 'p.tipo_bem_id', '=', 'tb.id')
            ->leftJoin('dependencias as d', 'p.dependencia_id', '=', 'd.id')
            ->leftJoin('usuarios as u', 'p.administrador_acessor_id', '=', 'u.id')
            ->where('p.comum_id', $churchId)
            ->where('p.imprimir_14_1', 1)
            ->where('p.ativo', 1)
            ->orderBy('p.codigo')
            ->get([
                'p.id_produto as id',
                'p.codigo',
                DB::raw("TRIM(CONCAT_WS(' ',
                    CASE WHEN (tb.codigo IS NOT NULL OR tb.descricao IS NOT NULL)
                         THEN TRIM(CONCAT_WS(' - ', tb.codigo, tb.descricao))
                         ELSE NULL END,
                    NULLIF(TRIM(COALESCE(NULLIF(p.editado_bem,''), p.bem)), ''),
                    NULLIF(TRIM(COALESCE(NULLIF(p.editado_complemento,''), p.complemento)), '')
                )) AS descricao_completa"),
                'p.condicao_14_1',
                'p.nota_numero',
                'p.nota_data',
                'p.nota_valor',
                'p.nota_fornecedor',
                'd.descricao as dependencia_descricao',
                'u.nome as administrador_nome',
                DB::raw('NULL as administrador_assinatura'),
                DB::raw('NULL as doador_nome'),
                DB::raw('NULL as doador_cpf'),
                DB::raw('NULL as doador_rg'),
                DB::raw('0 as doador_rg_igual_cpf'),
                DB::raw('0 as doador_casado'),
                DB::raw('NULL as doador_nome_conjuge'),
                DB::raw('NULL as doador_cpf_conjuge'),
                DB::raw('NULL as doador_rg_conjuge'),
                DB::raw('0 as doador_rg_conjuge_igual_cpf'),
                DB::raw('NULL as doador_assinatura'),
                DB::raw('NULL as doador_assinatura_conjuge'),
                DB::raw('NULL as doador_endereco_logradouro'),
                DB::raw('NULL as doador_endereco_numero'),
                DB::raw('NULL as doador_endereco_complemento'),
                DB::raw('NULL as doador_endereco_bairro'),
                DB::raw('NULL as doador_endereco_cidade'),
                DB::raw('NULL as doador_endereco_estado'),
                DB::raw('NULL as doador_endereco_cep'),
            ])
            ->map(static function ($item): array {
                $product = (array) $item;
                $product['nome_original'] = LegacyProductNameSupport::formatHistoricalName($product, false);
                $product['nome_atual'] = LegacyProductNameSupport::formatHistoricalName($product, true);

                return $product;
            })
            ->all();
    }

    /**
     * @return array<int, array{itens: array<int, array<string, mixed>>}>
     */
    private function loadProducts146(int $churchId): array
    {
        $products = DB::table('produtos as p')
            ->leftJoin('tipos_bens as tb', 'p.tipo_bem_id', '=', 'tb.id')
            ->leftJoin('tipos_bens as etb', 'p.editado_tipo_bem_id', '=', 'etb.id')
            ->leftJoin('dependencias as d', 'p.dependencia_id', '=', 'd.id')
            ->leftJoin('dependencias as ed', 'p.editado_dependencia_id', '=', 'ed.id')
            ->leftJoin('usuarios as u', 'p.administrador_acessor_id', '=', 'u.id')
            ->where('p.comum_id', $churchId)
            ->where('p.editado', 1)
            ->where('p.novo', 0)
            ->where('p.ativo', 1)
            ->orderBy('p.codigo')
            ->get([
                'p.id_produto as id',
                'p.codigo',
                'p.tipo_bem_id',
                'p.dependencia_id',
                'p.editado_tipo_bem_id',
                'p.editado_dependencia_id',
                'p.editado',
                'p.imprimir_etiqueta',
                'p.bem',
                'p.complemento',
                'p.editado_marca',
                'p.altura_m',
                'p.largura_m',
                'p.comprimento_m',
                'p.editado_bem',
                'p.editado_complemento',
                'p.editado_altura_m',
                'p.editado_largura_m',
                'p.editado_comprimento_m',
                'tb.codigo as tipo_codigo',
                'tb.descricao as tipo_descricao',
                'etb.codigo as editado_tipo_codigo',
                'etb.descricao as editado_tipo_descricao',
                'd.descricao as dependencia_descricao',
                'ed.descricao as editado_dependencia_descricao',
                'u.nome as administrador_nome',
            ])
            ->map(static function ($item): array {
                $product = (array) $item;
                $product['nome_original'] = LegacyProductNameSupport::formatHistoricalName($product, false);
                $product['nome_atual'] = LegacyProductNameSupport::formatHistoricalName($product, true);

                return $product;
            })
            ->all();

        $products = array_values(array_filter(
            $products,
            fn (array $product): bool => $this->hasRelevantEditForReport146($product)
        ));

        return array_map(
            static fn (array $chunk): array => ['itens' => $chunk],
            array_chunk($products, 13)
        );
    }

    /**
     * Retorna true apenas quando a edição altera descrição, localidade ou tipo de bem.
     *
     * @param array<string, mixed> $product
     */
    protected function hasRelevantEditForReport146(array $product): bool
    {
        $originalDescription = LegacyProductNameSupport::formatHistoricalName($product, false);
        $editedDescription = LegacyProductNameSupport::formatHistoricalName($product, true);

        if ($originalDescription !== $editedDescription) {
            return true;
        }

        $originalTypeId = (int) ($product['tipo_bem_id'] ?? 0);
        $editedTypeId = (int) ($product['editado_tipo_bem_id'] ?? 0);

        if ($originalTypeId !== $editedTypeId) {
            return true;
        }

        $originalDependencyId = (int) ($product['dependencia_id'] ?? 0);
        $editedDependencyId = (int) ($product['editado_dependencia_id'] ?? 0);

        return $originalDependencyId !== $editedDependencyId;
    }

    /**
     * @return array<int, array{id: int, descricao: string}>
     */
    private function loadChangeHistoryDependencies(int $churchId): array
    {
        $dependencyIds = DB::table('produtos')
            ->where('comum_id', $churchId)
            ->whereNotNull('dependencia_id')
            ->pluck('dependencia_id')
            ->merge(
                DB::table('produtos')
                    ->where('comum_id', $churchId)
                    ->where('editado', 1)
                    ->whereNotNull('editado_dependencia_id')
                    ->pluck('editado_dependencia_id')
            )
            ->map(static fn (mixed $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($dependencyIds->isEmpty()) {
            return [];
        }

        return DB::table('dependencias')
            ->whereIn('id', $dependencyIds->all())
            ->orderBy('descricao')
            ->get(['id', 'descricao'])
            ->map(static fn ($item): array => [
                'id' => (int) $item->id,
                'descricao' => (string) $item->descricao,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadChangeHistoryProducts(int $churchId, ?int $dependencyId): array
    {
        $query = DB::table('produtos as p')
            ->leftJoin('tipos_bens as tb', 'p.tipo_bem_id', '=', 'tb.id')
            ->leftJoin('tipos_bens as etb', 'p.editado_tipo_bem_id', '=', 'etb.id')
            ->leftJoin('dependencias as d_orig', 'p.dependencia_id', '=', 'd_orig.id')
            ->leftJoin('dependencias as d_edit', 'p.editado_dependencia_id', '=', 'd_edit.id')
            ->where('p.comum_id', $churchId);

        if ($dependencyId !== null) {
            $query->where(static function ($innerQuery) use ($dependencyId): void {
                $innerQuery
                    ->where(static function ($editedQuery) use ($dependencyId): void {
                        $editedQuery
                            ->where('p.editado', 1)
                            ->where('p.editado_dependencia_id', $dependencyId);
                    })
                    ->orWhere(static function ($originalQuery) use ($dependencyId): void {
                        $originalQuery
                            ->where(static function ($notEditedQuery): void {
                                $notEditedQuery
                                    ->whereNull('p.editado')
                                    ->orWhere('p.editado', 0);
                            })
                            ->where('p.dependencia_id', $dependencyId);
                    });
            });
        }

        return $query
            ->orderBy('p.codigo')
            ->get([
                'p.id_produto as id',
                'p.codigo',
                DB::raw('CAST(p.checado AS SIGNED) as checado'),
                DB::raw('CAST(p.ativo AS SIGNED) as ativo'),
                DB::raw('CAST(p.imprimir_etiqueta AS SIGNED) as imprimir'),
                'p.observacao as observacoes',
                DB::raw('CAST(p.editado AS SIGNED) as editado'),
                'p.bem',
                'p.complemento',
                'p.editado_marca',
                'p.altura_m',
                'p.largura_m',
                'p.comprimento_m',
                'p.editado_bem',
                'p.editado_complemento',
                'p.editado_altura_m',
                'p.editado_largura_m',
                'p.editado_comprimento_m',
                'tb.codigo as tipo_codigo',
                'tb.descricao as tipo_desc',
                'etb.codigo as editado_tipo_codigo',
                'etb.descricao as editado_tipo_desc',
                DB::raw("NULLIF(CONCAT_WS(' ', p.editado_bem, p.editado_complemento), '') as nome_editado"),
                'p.editado_dependencia_id as dependencia_editada',
                'd_orig.descricao as dependencia_desc',
                'd_edit.descricao as editado_dependencia_desc',
                DB::raw("COALESCE(d_edit.descricao, d_orig.descricao, '') as dependencia"),
                DB::raw("'comum' as origem"),
                DB::raw('NULL as quantidade'),
            ])
            ->map(function ($item): array {
                $product = (array) $item;
                $product['nome_original'] = $this->buildChangeHistoryTitle($product, false);
                $product['nome_atual'] = ((int) ($product['editado'] ?? 0) === 1 || trim((string) ($product['nome_editado'] ?? '')) !== '')
                    ? $this->buildChangeHistoryTitle($product, true)
                    : $product['nome_original'];

                return $product;
            })
            ->all();
    }

    /**
     * @param array<int, array<string, mixed>> $products
     * @return array<string, array{titulo: string, itens: array<int, array<string, mixed>>, total: int}>
     */
    private function classifyChangeHistoryProducts(array $products): array
    {
        $sections = [
            'pendentes' => ['titulo' => 'Pendentes', 'itens' => []],
            'checados' => ['titulo' => 'Checados', 'itens' => []],
            'observacao' => ['titulo' => 'Com observação', 'itens' => []],
            'checados_observacao' => ['titulo' => 'Checados com observação', 'itens' => []],
            'etiqueta' => ['titulo' => 'Para impressão de etiquetas', 'itens' => []],
            'checados_etiqueta' => ['titulo' => 'Checados para etiquetas', 'itens' => []],
            'observacao_etiqueta' => ['titulo' => 'Com observação para etiquetas', 'itens' => []],
            'checados_observacao_etiqueta' => ['titulo' => 'Checados com observação para etiquetas', 'itens' => []],
            'alteracoes' => ['titulo' => 'Editados', 'itens' => []],
            'novos' => ['titulo' => 'Novos', 'itens' => []],
        ];

        foreach ($products as $product) {
            if (($product['origem'] ?? '') === 'cadastro') {
                $sections['novos']['itens'][] = $product;

                if (!empty($product['codigo'])) {
                    $sections['etiqueta']['itens'][] = $product;
                }

                continue;
            }

            $hasObservation = trim((string) ($product['observacoes'] ?? '')) !== '';
            $isChecked = (int) ($product['checado'] ?? 0) === 1;
            $printLabel = (int) ($product['imprimir'] ?? 0) === 1;
            $hasChanges = (int) ($product['editado'] ?? 0) === 1;
            $isPending = ($product['checado'] ?? null) === null
                && (int) ($product['ativo'] ?? 1) === 1
                && ($product['imprimir'] ?? null) === null
                && ($product['observacoes'] ?? null) === null
                && ($product['editado'] ?? null) === null;

            if ($hasChanges) {
                $sections['alteracoes']['itens'][] = $product;
                $sections['etiqueta']['itens'][] = $product;
            }

            if ($printLabel && $hasObservation && $isChecked) {
                $sections['checados_observacao_etiqueta']['itens'][] = $product;
            } elseif ($printLabel && $isChecked) {
                $sections['checados_etiqueta']['itens'][] = $product;
            } elseif ($printLabel && $hasObservation) {
                $sections['observacao_etiqueta']['itens'][] = $product;
            } elseif ($printLabel) {
                $sections['etiqueta']['itens'][] = $product;
            } elseif ($hasObservation && $isChecked) {
                $sections['checados_observacao']['itens'][] = $product;
            } elseif ($hasObservation) {
                $sections['observacao']['itens'][] = $product;
            } elseif ($isChecked) {
                $sections['checados']['itens'][] = $product;
            } elseif ($isPending) {
                $sections['pendentes']['itens'][] = $product;
            } else {
                $sections['pendentes']['itens'][] = $product;
            }
        }

        foreach ($sections as $key => $section) {
            $sections[$key]['total'] = count($section['itens']);
        }

        return $sections;
    }

    /**
     * @param array<string, mixed> $product
     */
    private function buildChangeHistoryTitle(array $product, bool $useEdited): string
    {
        $typeCode = trim((string) ($useEdited ? ($product['editado_tipo_codigo'] ?? '') : ($product['tipo_codigo'] ?? '')));
        $typeDescription = trim((string) ($useEdited ? ($product['editado_tipo_desc'] ?? '') : ($product['tipo_desc'] ?? '')));
        $typePart = '';

        if ($typeCode !== '' || $typeDescription !== '') {
            $typePart = '{' . mb_strtoupper(trim(($typeCode !== '' ? $typeCode . ' - ' : '') . $typeDescription), 'UTF-8') . '}';
        }

        $description = LegacyProductNameSupport::formatHistoricalName($product, $useEdited);

        $dependencyDescription = trim((string) ($useEdited
            ? ($product['editado_dependencia_desc'] ?? $product['dependencia_desc'] ?? '')
            : ($product['dependencia_desc'] ?? '')));
        $dependencyPart = $dependencyDescription !== '' ? ' {' . mb_strtoupper($dependencyDescription, 'UTF-8') . '}' : '';
        $title = trim(($typePart !== '' ? $typePart . ' ' : '') . $description . ($dependencyPart !== '' ? ' ' . $dependencyPart : ''));

        return $title !== '' ? $title : 'Sem descrição';
    }

    private function formatShortCode(?string $code): string
    {
        $normalizedCode = trim((string) $code);

        if ($normalizedCode === '') {
            return '';
        }

        $parts = explode('/', $normalizedCode);

        return trim((string) end($parts));
    }
}
