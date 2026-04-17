<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LegacyReportServiceInterface;
use App\Models\Legacy\Comum;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

    public function buildChangeHistory(int $churchId, array $filters): array
    {
        $churchData = $this->loadChurchData($churchId);

        if ($churchData === []) {
            throw new RuntimeException('Igreja não encontrada para abrir o histórico de alterações.');
        }

        $normalizedFilters = [
            'mostrar_pendentes' => (bool) ($filters['mostrar_pendentes'] ?? false),
            'mostrar_checados' => (bool) ($filters['mostrar_checados'] ?? false),
            'mostrar_observacao' => (bool) ($filters['mostrar_observacao'] ?? false),
            'mostrar_checados_observacao' => (bool) ($filters['mostrar_checados_observacao'] ?? false),
            'mostrar_etiqueta' => (bool) ($filters['mostrar_etiqueta'] ?? false),
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
            'total_alteracoes' => count($sections['alteracoes']['itens']),
            'total_novos' => count($sections['novos']['itens']),
            'total_mostrar' => 0,
        ];

        foreach ([
            'mostrar_pendentes' => 'total_pendentes',
            'mostrar_checados' => 'total_checados',
            'mostrar_observacao' => 'total_observacao',
            'mostrar_checados_observacao' => 'total_checados_observacao',
            'mostrar_etiqueta' => 'total_etiqueta',
            'mostrar_alteracoes' => 'total_alteracoes',
            'mostrar_novos' => 'total_novos',
        ] as $flag => $totalKey) {
            if ($normalizedFilters[$flag] === true) {
                $totals['total_mostrar'] += $totals[$totalKey];
            }
        }

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
        $result = DB::table('comums')
            ->select([
                'cnpj',
                'descricao',
                DB::raw('cidade_administracao AS administracao'),
                'cidade',
                'setor',
                'estado',
                'estado_administracao',
                'cidade_administracao',
            ])
            ->where('id', $churchId)
            ->first();

        return $result !== null ? (array) $result : [];
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
            ->map(static fn ($item): array => (array) $item)
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
                'p.editado',
                'p.imprimir_etiqueta',
                'p.bem',
                'p.complemento',
                'p.editado_bem',
                'p.editado_complemento',
                'tb.codigo as tipo_codigo',
                'tb.descricao as tipo_descricao',
                'etb.codigo as editado_tipo_codigo',
                'etb.descricao as editado_tipo_descricao',
                'd.descricao as dependencia_descricao',
                'ed.descricao as editado_dependencia_descricao',
                'u.nome as administrador_nome',
            ])
            ->map(static fn ($item): array => (array) $item)
            ->all();

        return array_map(
            static fn (array $chunk): array => ['itens' => $chunk],
            array_chunk($products, 13)
        );
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
                'p.editado_bem',
                'p.editado_complemento',
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

            if ($printLabel) {
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

        $asset = trim((string) ($useEdited ? ($product['editado_bem'] ?? '') : ($product['bem'] ?? '')));
        $complement = trim((string) ($useEdited ? ($product['editado_complemento'] ?? '') : ($product['complemento'] ?? '')));
        $description = $asset;

        if ($complement !== '') {
            $trimmedComplement = $complement;

            if ($asset !== '' && mb_strtoupper(mb_substr($trimmedComplement, 0, mb_strlen($asset), 'UTF-8'), 'UTF-8') === mb_strtoupper($asset, 'UTF-8')) {
                $trimmedComplement = trim(mb_substr($trimmedComplement, mb_strlen($asset), null, 'UTF-8'));
                $trimmedComplement = preg_replace('/^[\s\-\/]+/u', '', $trimmedComplement) ?? $trimmedComplement;
            }

            if ($trimmedComplement !== '') {
                $description .= ($description !== '' ? ' ' : '') . $trimmedComplement;
            }
        }

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
