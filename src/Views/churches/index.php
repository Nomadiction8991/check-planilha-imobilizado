<?php


use App\Helpers\{AlertHelper, PaginationHelper, ViewHelper};

$comuns ??= [];
$total ??= 0;
$pagina ??= 1;
$totalPaginas ??= 1;
$busca ??= '';
$limite ??= 10;
$projectRoot ??= (require dirname(__DIR__, 3) . '/config/app.php')['project_root'];
?>

<?= AlertHelper::fromQuery() ?>

<?php
$filterCardOptions = [
    'titulo'       => 'PESQUISAR COMUM',
    'icone'        => 'bi-search',
    'campos'       => [
        [
            'tipo'        => 'text',
            'name'        => 'busca',
            'label'       => 'Código ou descrição',
            'value'       => $busca,
            'placeholder' => 'Código ou descrição',
        ],
    ],
];
include $projectRoot . '/src/Views/layouts/partials/filter-card.php';
?>

<?= PaginationHelper::info($total, $pagina, $limite) ?>

<?php
// Gerar linhas da tabela
ob_start();
if (!empty($comuns)):
    foreach ($comuns as $comum):
        $codigo = preg_replace("/\D/", '', (string)($comum['codigo'] ?? ''));
        if ($codigo === '') {
            $codigoFormatado = 'BR --';
        } else {
            $codigo = str_pad($codigo, 6, '0', STR_PAD_LEFT);
            $codigoFormatado = 'BR ' . substr($codigo, 0, 2) . '-' . substr($codigo, 2);
        }
        $editUrl = ViewHelper::urlComQuery('/churches/edit', ['id' => $comum['id']]);
        ?>
        <tr style="border-bottom:1px solid #e5e5e5" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
            <td class="px-5 py-3" style="font-weight:600;color:#000;font-family:Monaco,'Courier New',monospace;letter-spacing:0.04em">
                <?= ViewHelper::e($codigoFormatado) ?>
            </td>
            <td class="px-5 py-3" style="color:#000;text-transform:uppercase">
                <?= ViewHelper::e($comum['descricao'] ?? '') ?>
            </td>
            <td class="px-5 py-3">
                <div style="display:flex;justify-content:center;gap:6px">
                    <a href="<?= ViewHelper::e($editUrl) ?>"
                        class="btn btn-action-edit"
                        style="padding:5px 10px;font-size:12px;text-decoration:none"
                        title="Editar">
                        <i class="bi bi-pencil" style="font-size:11px"></i>
                    </a>
                    <button
                        type="button"
                        class="btn btn-action-delete btn-delete-products"
                        style="padding:5px 10px;font-size:12px"
                        data-comum-id="<?= (int)$comum['id'] ?>"
                        data-comum-nome="<?= ViewHelper::e($comum['descricao'] ?? '') ?>"
                        title="Excluir todos os produtos">
                        <i class="bi bi-trash3" style="font-size:11px"></i>
                    </button>
                </div>
            </td>
        </tr>
        <?php
    endforeach;
endif;
$linhasHtml = ob_get_clean();
?>

<?php
$tableOptions = [
    'icone'          => 'bi-church',
    'titulo'         => 'IGREJAS',
    'total'          => count($comuns),
    'pagina'         => $pagina,
    'total_paginas'  => $totalPaginas,
    'colunas'        => ['CÓDIGO', 'DESCRIÇÃO', 'AÇÕES'],
    'empty_msg'      => 'Nenhuma comum encontrada',
    'linhas_html'    => $linhasHtml,
    'paginacao_html' => PaginationHelper::render($pagina, $totalPaginas, '/churches', ['busca' => $busca]),
];
include $projectRoot . '/src/Views/layouts/partials/table-wrapper.php';
?>

<!-- Modal: Confirmar exclusão de produtos -->
<div style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:50;align-items:center;justify-content:center" id="modalDeleteProdutos">
    <div style="background:#fff;border:1px solid #e5e5e5;max-width:480px;width:90%;border-radius:2px">
        <div style="padding:16px 20px;border-bottom:2px solid #991b1b;display:flex;align-items:center;gap:8px">
            <i class="bi bi-exclamation-triangle-fill" style="color:#991b1b;font-size:18px;flex-shrink:0"></i>
            <strong style="font-size:14px;letter-spacing:0.04em">EXCLUIR PRODUTOS</strong>
        </div>
        <div style="padding:20px;font-size:14px;color:#262626;line-height:1.6">
            <p>Deseja excluir <strong>TODOS OS PRODUTOS</strong> do comum:</p>
            <p style="font-weight:700;margin:8px 0;text-transform:uppercase" id="modalDeleteNomeComum"></p>
            <p id="modalDeleteCount" style="color:#525252;font-size:13px">Carregando...</p>
            <p style="color:#991b1b;font-size:12px;margin-top:12px;display:flex;align-items:center;gap:6px">
                <i class="bi bi-exclamation-circle"></i>Esta ação <strong>não pode ser desfeita</strong>.
            </p>
        </div>
        <div style="padding:12px 20px;border-top:1px solid #e5e5e5;background:#fafafa;display:flex;gap:8px;justify-content:flex-end">
            <button type="button"
                style="padding:8px 16px;border:1px solid #d4d4d4;background:#fff;font-size:13px;cursor:pointer;border-radius:2px"
                onclick="document.getElementById('modalDeleteProdutos').style.display='none'">
                Cancelar
            </button>
            <form id="formDeleteProdutos" method="POST" action="/churches/delete-products" style="display:inline">
                <input type="hidden" name="comum_id" id="deleteComumId">
                <?= \App\Core\CsrfService::hiddenField() ?>
                <button type="submit" id="btnConfirmDeleteProdutos"
                    style="padding:8px 16px;background:#991b1b;color:#fff;border:1px solid #991b1b;font-size:13px;cursor:pointer;border-radius:2px"
                    disabled>
                    <i class="bi bi-trash3"></i> Excluir tudo
                </button>
            </form>
        </div>
    </div>
</div>

<script src="/assets/js/churches/index.js"></script>
