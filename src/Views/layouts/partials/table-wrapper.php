<?php
/**
 * Partial: Card de tabela reutilizável com header, corpo, e paginação
 *
 * Variáveis esperadas via $tableOptions:
 * - icone (string): Classe Bootstrap Icons (ex: 'bi-people')
 * - titulo (string): Título da tabela
 * - total (int): Total de registros
 * - pagina (int): Número da página atual
 * - total_paginas (int): Total de páginas
 * - colunas (array): Array de nomes de colunas ['NOME', 'EMAIL', 'AÇÕES']
 * - empty_msg (string): Mensagem quando lista vazia
 * - linhas_html (string): HTML gerado com as linhas da tabela (via ob_start)
 * - paginacao_html (string): HTML da paginação (via PaginationHelper::render)
 */

$tableOptions ??= [];
$icone = $tableOptions['icone'] ?? 'bi-box-seam';
$titulo = $tableOptions['titulo'] ?? 'LISTA';
$total = $tableOptions['total'] ?? 0;
$pagina = $tableOptions['pagina'] ?? 1;
$total_paginas = $tableOptions['total_paginas'] ?? 1;
$colunas = $tableOptions['colunas'] ?? [];
$empty_msg = $tableOptions['empty_msg'] ?? 'Nenhum item encontrado';
$linhas_html = $tableOptions['linhas_html'] ?? '';
$paginacao_html = $tableOptions['paginacao_html'] ?? '';
?>

<div class="bg-white border border-neutral-200" style="border-radius:2px">
    <!-- Header -->
    <div class="px-5 py-3 border-b border-neutral-200 bg-neutral-50 flex justify-between items-center" style="gap:12px;flex-wrap:wrap">
        <span style="font-size:12px;font-weight:600;letter-spacing:0.06em;color:#525252;display:flex;align-items:center;gap:6px">
            <i class="<?= htmlspecialchars($icone) ?>" style="font-size:13px"></i><?= htmlspecialchars($titulo) ?>
        </span>
        <span style="font-size:11px;color:#808080;background:#fff;border:1px solid #e5e5e5;padding:2px 8px;border-radius:2px;white-space:nowrap">
            <?= (int)$total ?> itens · pág. <?= (int)$pagina ?>/<?= ((int)$total_paginas ?: 1) ?>
        </span>
    </div>

    <!-- Tabela -->
    <div class="overflow-x-auto">
        <table class="w-full border-collapse" style="font-size:13px">
            <?php if (!empty($colunas)): ?>
                <thead>
                    <tr style="background:#fafafa;border-bottom:2px solid #000">
                        <?php foreach ($colunas as $col): ?>
                            <th class="px-5 py-3 text-left" style="font-size:11px;font-weight:700;letter-spacing:0.08em;color:#000">
                                <?= htmlspecialchars($col) ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
            <?php endif; ?>

            <tbody>
                <?php if (!empty($linhas_html)): ?>
                    <?php echo $linhas_html; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?= count($colunas) ?: 1 ?>" class="px-5 py-10 text-center" style="color:#a3a3a3;font-size:13px">
                            <i class="bi bi-inbox block mb-2" style="font-size:24px"></i>
                            <?= htmlspecialchars($empty_msg) ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginação -->
    <?php if (!empty($paginacao_html)): ?>
        <div class="px-5 py-3 border-t border-neutral-100 bg-neutral-50">
            <?php echo $paginacao_html; ?>
        </div>
    <?php endif; ?>
</div>
