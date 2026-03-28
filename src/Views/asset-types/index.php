<?php


use App\Helpers\{AlertHelper, PaginationHelper, ViewHelper};

$tipos        = $tipos        ?? [];
$total        = $total        ?? 0;
$pagina       = $pagina       ?? 1;
$totalPaginas = $totalPaginas ?? 1;
$busca        = $busca        ?? '';
$limite       = $limite       ?? 20;
$projectRoot  = $projectRoot  ?? (require dirname(__DIR__, 3) . '/config/app.php')['project_root'];
?>

<?= AlertHelper::fromQuery() ?>

<?php
$filterCardOptions = [
    'titulo'       => 'PESQUISAR TIPO DE BEM',
    'icone'        => 'bi-search',
    'campos'       => [
        [
            'tipo'        => 'text',
            'name'        => 'busca',
            'label'       => 'Descrição',
            'value'       => $busca,
            'placeholder' => 'Descrição',
        ],
    ],
];
include $projectRoot . '/src/Views/layouts/partials/filter-card.php';
?>

<?= PaginationHelper::info($total, $pagina, $limite) ?>

<?php
// Gerar linhas da tabela
ob_start();
if (!empty($tipos)):
    foreach ($tipos as $tipo):
        ?>
        <tr style="border-bottom:1px solid #e5e5e5" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
            <td class="px-5 py-3" style="color:#000;text-transform:uppercase">
                <?= htmlspecialchars($tipo['descricao'], ENT_QUOTES, 'UTF-8') ?>
            </td>
            <td class="px-5 py-3 text-center">
                <a href="/asset-types/<?= (int)$tipo['id'] ?>/edit"
                    style="display:inline-flex;align-items:center;padding:5px 10px;border:1px solid #000;color:#000;font-size:12px;text-decoration:none;border-radius:2px;transition:background 120ms"
                    onmouseover="this.style.background='#000';this.style.color='#fff'"
                    onmouseout="this.style.background='';this.style.color='#000'"
                    title="Editar">
                    <i class="bi bi-pencil" style="font-size:11px"></i>
                </a>
            </td>
        </tr>
        <?php
    endforeach;
endif;
$linhasHtml = ob_get_clean();
?>

<?php
$tableOptions = [
    'icone'          => 'bi-box-seam',
    'titulo'         => 'TIPOS DE BENS',
    'total'          => count($tipos),
    'pagina'         => $pagina,
    'total_paginas'  => $totalPaginas,
    'colunas'        => ['DESCRIÇÃO', 'AÇÕES'],
    'empty_msg'      => 'Nenhum tipo de bem encontrado',
    'linhas_html'    => $linhasHtml,
    'paginacao_html' => PaginationHelper::render($pagina, $totalPaginas, '/asset-types', ['busca' => $busca]),
];
include $projectRoot . '/src/Views/layouts/partials/table-wrapper.php';
?>

<?php if (isset($erro)): ?>
    <div class="p-5 mb-4" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;font-size:13px;border-radius:2px">
        <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<a href="/asset-types/create"
    class="flex items-center justify-center gap-2 w-full px-4 py-2 bg-black text-white text-sm font-medium hover:bg-neutral-900 transition mb-4"
    style="border-radius:2px;text-decoration:none">
    <i class="bi bi-plus-circle"></i>Novo Tipo de Bem
</a>
