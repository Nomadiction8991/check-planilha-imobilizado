<?php


use App\Helpers\{AlertHelper, PaginationHelper, ViewHelper};

$comuns ??= [];
$total ??= 0;
$pagina ??= 1;
$totalPaginas ??= 1;
$busca ??= '';
$limite ??= 10;
?>

<?= AlertHelper::fromQuery() ?>

<div class="bg-white border border-neutral-200 mb-4" style="border-radius:2px">
    <div class="px-5 py-3 border-b border-neutral-200 bg-neutral-50 flex items-center gap-2">
        <i class="bi bi-search text-neutral-500" style="font-size:13px"></i>
        <span style="font-size:12px;font-weight:600;letter-spacing:0.06em;color:#525252">PESQUISAR COMUM</span>
    </div>
    <div class="p-5">
        <form method="GET" class="flex gap-2">
            <input
                type="text"
                name="busca"
                id="busca"
                class="flex-1 px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black"
                style="border-radius:2px"
                value="<?= ViewHelper::e($busca) ?>"
                placeholder="Código ou descrição">
            <?php if ($busca): ?>
                <a href="?"
                    style="display:inline-flex;align-items:center;padding:8px 12px;border:1px solid #d4d4d4;color:#525252;font-size:13px;text-decoration:none;border-radius:2px"
                    title="Limpar">
                    <i class="bi bi-x-lg"></i>
                </a>
            <?php endif; ?>
            <button type="submit"
                class="px-4 py-2 bg-black text-white text-sm font-medium hover:bg-neutral-900 transition flex items-center gap-2"
                style="border-radius:2px">
                <i class="bi bi-search"></i>Buscar
            </button>
        </form>
    </div>
</div>

<?= PaginationHelper::info($total, $pagina, $limite) ?>

<div class="bg-white border border-neutral-200 overflow-x-auto" style="border-radius:2px">
    <table class="w-full border-collapse" style="font-size:13px">
        <thead>
            <tr style="background:#fafafa;border-bottom:2px solid #000">
                <th class="px-5 py-3 text-left" style="font-size:11px;font-weight:700;letter-spacing:0.08em;color:#000;width:140px">CÓDIGO</th>
                <th class="px-5 py-3 text-left" style="font-size:11px;font-weight:700;letter-spacing:0.08em;color:#000">DESCRIÇÃO</th>
                <th class="px-5 py-3 text-center" style="font-size:11px;font-weight:700;letter-spacing:0.08em;color:#000;width:100px">AÇÕES</th>
            </tr>
        </thead>
        <tbody id="tabela-comuns">
            <?php if (empty($comuns)): ?>
                <tr>
                    <td colspan="3" class="text-center py-10" style="color:#a3a3a3;font-size:13px">
                        <i class="bi bi-inbox block mb-2" style="font-size:24px"></i>
                        Nenhuma comum encontrada
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($comuns as $comum): ?>
                    <?php
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
                                    style="display:inline-flex;align-items:center;padding:5px 10px;border:1px solid #000;color:#000;font-size:12px;text-decoration:none;border-radius:2px;transition:background 120ms"
                                    onmouseover="this.style.background='#000';this.style.color='#fff'"
                                    onmouseout="this.style.background='';this.style.color='#000'"
                                    title="Editar">
                                    <i class="bi bi-pencil" style="font-size:11px"></i>
                                </a>
                                <button
                                    type="button"
                                    style="display:inline-flex;align-items:center;padding:5px 10px;border:1px solid #991b1b;color:#991b1b;font-size:12px;background:#fff;cursor:pointer;border-radius:2px;transition:background 120ms"
                                    onmouseover="this.style.background='#991b1b';this.style.color='#fff'"
                                    onmouseout="this.style.background='#fff';this.style.color='#991b1b'"
                                    class="btn-delete-products"
                                    data-comum-id="<?= (int)$comum['id'] ?>"
                                    data-comum-nome="<?= ViewHelper::e($comum['descricao'] ?? '') ?>"
                                    title="Excluir todos os produtos">
                                    <i class="bi bi-trash3" style="font-size:11px"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?= PaginationHelper::render($pagina, $totalPaginas, '/churches', ['busca' => $busca]) ?>

<!-- Modal: Confirmar exclusão de produtos -->
<div style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:50" id="modalDeleteProdutos">
    <div style="background:#fff;border:1px solid #e5e5e5;max-width:480px;width:90%;border-radius:2px">
        <div style="padding:16px 20px;border-bottom:2px solid #991b1b;display:flex;align-items:center;gap-8px">
            <i class="bi bi-exclamation-triangle-fill" style="color:#991b1b;margin-right:8px"></i>
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
        <div style="padding:12px 20px;border-top:1px solid #e5e5e5;display:flex;justify-content:flex-end;gap:8px">
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
