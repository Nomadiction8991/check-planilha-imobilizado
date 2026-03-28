<?php


$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
require_once $projectRoot . '/src/Helpers/BootstrapLoader.php';

$id_planilha = $comum_id;
$PRODUTOS = $produtos ?? [];

$pageTitle = 'VISUALIZAR PRODUTOS';
$backUrl = '/products/view?comum_id=' . urlencode($comum_id);

ob_start();
?>

<!-- Filtros -->
<div class="bg-white border border-neutral-200 mb-4" style="border-radius:2px">
    <div class="px-5 py-3 border-b border-neutral-200 bg-neutral-50 flex items-center gap-2">
        <i class="bi bi-funnel text-neutral-500" style="font-size:13px"></i>
        <span style="font-size:12px;font-weight:600;letter-spacing:0.06em;color:#525252">FILTROS</span>
    </div>
    <div class="p-5">
        <form method="GET" id="filtros-form">
            <input type="hidden" name="comum_id" value="<?= htmlspecialchars($comum_id) ?>">

            <div class="mb-3">
                <label for="filtro_complemento" style="display:block;font-size:12px;font-weight:500;color:#262626;margin-bottom:4px">
                    Pesquisar por Descrição
                </label>
                <input type="text" id="filtro_complemento" name="filtro_complemento"
                    class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black"
                    style="border-radius:2px"
                    value="<?= htmlspecialchars($filtro_complemento ?? '') ?>"
                    placeholder="Digite para buscar...">
            </div>

            <!-- Filtros avançados (accordion nativo) -->
            <details class="mb-3">
                <summary style="cursor:pointer;font-size:12px;font-weight:500;color:#525252;padding:8px 0;display:flex;align-items:center;gap:6px;list-style:none;user-select:none">
                    <i class="bi bi-sliders" style="font-size:12px"></i>
                    Filtros Avançados
                    <i class="bi bi-chevron-down" style="font-size:10px;margin-left:auto" id="filtros-chevron"></i>
                </summary>
                <div style="padding-top:12px;border-top:1px solid #e5e5e5;margin-top:8px;display:flex;flex-direction:column;gap:12px">

                    <div>
                        <label for="pesquisa_id" style="display:block;font-size:12px;font-weight:500;color:#262626;margin-bottom:4px">ID</label>
                        <input type="number" id="pesquisa_id" name="pesquisa_id"
                            class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black"
                            style="border-radius:2px"
                            value="<?= htmlspecialchars($pesquisa_id ?? '') ?>"
                            placeholder="Digite o ID">
                    </div>

                    <div>
                        <label for="filtro_tipo_ben" style="display:block;font-size:12px;font-weight:500;color:#262626;margin-bottom:4px">Tipo de Bem</label>
                        <select id="filtro_tipo_ben" name="filtro_tipo_ben"
                            class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black"
                            style="border-radius:2px">
                            <option value="">Todos</option>
                            <?php foreach ($tipos_bens as $tipo): ?>
                                <option value="<?= $tipo['id'] ?>" <?= ($filtro_tipo_ben ?? '') == $tipo['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tipo['codigo'] . ' - ' . $tipo['descricao']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="filtro_bem" style="display:block;font-size:12px;font-weight:500;color:#262626;margin-bottom:4px">Bem</label>
                        <select id="filtro_bem" name="filtro_bem"
                            class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black"
                            style="border-radius:2px">
                            <option value="">Todos</option>
                            <?php foreach ($bem_codigos as $bem): ?>
                                <option value="<?= htmlspecialchars((string)$bem) ?>" <?= ($filtro_bem ?? '') == $bem ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string)$bem) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="filtro_dependencia" style="display:block;font-size:12px;font-weight:500;color:#262626;margin-bottom:4px">Dependência</label>
                        <select id="filtro_dependencia" name="filtro_dependencia"
                            class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black"
                            style="border-radius:2px">
                            <option value="">Todas</option>
                            <?php foreach ($dependencias as $dep): ?>
                                <option value="<?= $dep['id'] ?>" <?= ($filtro_dependencia ?? '') == $dep['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dep['descricao']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="filtro_status" style="display:block;font-size:12px;font-weight:500;color:#262626;margin-bottom:4px">Status</label>
                        <select id="filtro_status" name="filtro_status"
                            class="w-full px-3 py-2 border border-neutral-300 text-sm focus:outline-none focus:border-black"
                            style="border-radius:2px">
                            <option value="">Todos</option>
                            <option value="com_nota" <?= ($filtro_STATUS ?? '') === 'com_nota'  ? 'selected' : '' ?>>Com Nota</option>
                            <option value="com_14_1" <?= ($filtro_STATUS ?? '') === 'com_14_1'  ? 'selected' : '' ?>>Com 14.1</option>
                            <option value="sem_status" <?= ($filtro_STATUS ?? '') === 'sem_status' ? 'selected' : '' ?>>Sem Status</option>
                        </select>
                    </div>
                </div>
            </details>

            <button type="submit"
                class="w-full px-4 py-2 bg-black text-white text-sm font-medium hover:bg-neutral-900 transition flex items-center justify-center gap-2"
                style="border-radius:2px">
                <i class="bi bi-search"></i>Filtrar
            </button>
        </form>
    </div>
    <div class="px-5 py-2 border-t border-neutral-100 bg-neutral-50" style="font-size:12px;color:#808080">
        <?= $total_registros ?? 0 ?> registros encontrados
    </div>
</div>

<!-- Exclusão em massa -->
<div id="deleteButtonContainer" class="bg-white border border-neutral-200 mb-4" style="border-radius:2px;display:none">
    <div class="px-5 py-3">
        <form method="POST" id="deleteForm" action="/products/delete">
            <input type="hidden" name="id_planilha" value="<?= htmlspecialchars($id_planilha) ?>">
            <div id="selectedProducts"></div>
            <button type="button" id="openDeleteModalButton"
                class="w-full px-4 py-2 text-white text-sm font-medium flex items-center justify-center gap-2"
                style="background:#991b1b;border:1px solid #991b1b;border-radius:2px">
                <i class="bi bi-trash"></i>
                Excluir <span id="countSelected">0</span> produto(s) selecionado(s)
            </button>
        </form>
    </div>
</div>

<!-- Modal de confirmação -->
<div id="confirmDeleteModal" style="display:none;position:fixed;inset:0;z-index:50;background:rgba(0,0,0,0.5);align-items:center;justify-content:center">
    <div style="background:#fff;border:1px solid #e5e5e5;max-width:440px;width:90%;border-radius:2px">
        <div style="padding:14px 20px;border-bottom:1px solid #e5e5e5">
            <strong style="font-size:14px">Confirmar exclusão</strong>
        </div>
        <div style="padding:16px 20px;font-size:14px;color:#262626">
            Tem certeza que deseja excluir os produtos selecionados?
        </div>
        <div style="padding:12px 20px;border-top:1px solid #e5e5e5;background:#fafafa;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" id="cancelDeleteModalButton"
                style="padding:7px 14px;border:1px solid #d4d4d4;background:#fff;font-size:13px;cursor:pointer;border-radius:2px">
                Cancelar
            </button>
            <button type="button" id="confirmDeleteButton"
                style="padding:7px 14px;background:#991b1b;color:#fff;border:1px solid #991b1b;font-size:13px;cursor:pointer;border-radius:2px">
                Excluir
            </button>
        </div>
    </div>
</div>

<!-- Lista de produtos -->
<div class="bg-white border border-neutral-200" style="border-radius:2px">
    <div class="px-5 py-3 border-b border-neutral-200 bg-neutral-50 flex justify-between items-center">
        <span style="font-size:12px;font-weight:600;letter-spacing:0.06em;color:#525252;display:flex;align-items:center;gap:6px">
            <i class="bi bi-box-seam" style="font-size:13px"></i>PRODUTOS
        </span>
        <span style="font-size:11px;color:#808080;background:#fff;border:1px solid #e5e5e5;padding:2px 8px;border-radius:2px">
            <?= $total_registros ?? 0 ?> itens · pág. <?= $pagina ?>/<?= $total_paginas ?: 1 ?>
        </span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full border-collapse" style="font-size:13px">
            <thead>
                <tr style="background:#fafafa;border-bottom:2px solid #000">
                    <th class="px-5 py-3 text-left" style="font-size:11px;font-weight:700;letter-spacing:0.08em;color:#000">PRODUTO</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($PRODUTOS)): ?>
                    <?php foreach ($PRODUTOS as $PRODUTO): ?>
                        <tr style="border-bottom:1px solid #e5e5e5" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                            <td class="px-5 py-3">
                                <div style="display:flex;gap:10px">
                                    <div style="margin-top:2px">
                                        <input class="PRODUTO-checkbox" type="checkbox"
                                            value="<?= $PRODUTO['id_produto'] ?>"
                                            id="PRODUTO_<?= $PRODUTO['id_produto'] ?>"
                                            style="width:14px;height:14px;cursor:pointer;accent-color:#000">
                                    </div>
                                    <div style="flex:1;min-width:0">
                                        <div style="font-weight:600;color:#000;margin-bottom:6px;line-height:1.3">
                                            <?php
                                            $tipoCod  = trim((string)($PRODUTO['tipo_bem_codigo'] ?? ''));
                                            $tipoDesc = trim((string)($PRODUTO['tipo_bem_descricao'] ?? ''));
                                            $tipoPart = '';
                                            if ($tipoCod !== '' || $tipoDesc !== '') {
                                                $tipoPart = '{' . mb_strtoupper(trim(($tipoCod ? $tipoCod . ' - ' : '') . $tipoDesc), 'UTF-8') . '}';
                                            }
                                            $bemProd  = trim((string)($PRODUTO['bem'] ?? ''));
                                            $compProd = trim((string)($PRODUTO['complemento'] ?? ''));
                                            $descProd = mb_strtoupper($bemProd . ($compProd !== '' ? ' ' . $compProd : ''), 'UTF-8');
                                            $dep      = trim((string)($PRODUTO['dependencia_descricao'] ?? $PRODUTO['dependencia_desc'] ?? ''));
                                            $depPart  = $dep !== '' ? ' {' . mb_strtoupper($dep, 'UTF-8') . '}' : '';
                                            echo htmlspecialchars(trim(($tipoPart ? $tipoPart . ' ' : '') . $descProd . $depPart));
                                            ?>
                                        </div>
                                        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap">
                                            <div style="display:flex;gap:4px;flex-wrap:wrap">
                                                <?php if (!empty($PRODUTO['codigo'])): ?>
                                                    <span style="display:inline-block;padding:2px 7px;font-size:11px;font-weight:600;background:#f0f9ff;color:#0369a1;border:1px solid #06b6d4;border-radius:2px;font-family:Monaco,'Courier New',monospace">
                                                        <?= \App\Helpers\ViewHelper::e(\App\Helpers\ViewHelper::formatarCodigoCurto($PRODUTO['codigo'] ?? '')) ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (isset($PRODUTO['condicao_141']) && ($PRODUTO['condicao_141'] == 1 || $PRODUTO['condicao_141'] == 3)): ?>
                                                    <span style="display:inline-block;padding:2px 7px;font-size:11px;font-weight:600;background:#fefce8;color:#b45309;border:1px solid #fde047;border-radius:2px">Nota</span>
                                                <?php endif; ?>
                                                <?php if ($PRODUTO['imprimir_14_1'] == 1): ?>
                                                    <span style="display:inline-block;padding:2px 7px;font-size:11px;font-weight:600;background:#000;color:#fff;border-radius:2px">14.1</span>
                                                <?php endif; ?>
                                            </div>
                                            <a href="/products/edit?id_produto=<?= $PRODUTO['id_produto'] ?>&comum_id=<?= $comum_id ?>&<?= gerarParametrosFiltro(true) ?>"
                                                style="display:inline-flex;align-items:center;padding:4px 10px;border:1px solid #000;color:#000;font-size:12px;text-decoration:none;border-radius:2px;flex-shrink:0;transition:background 120ms"
                                                onmouseover="this.style.background='#000';this.style.color='#fff'"
                                                onmouseout="this.style.background='';this.style.color='#000'"
                                                title="Editar">
                                                <i class="bi bi-pencil-fill" style="font-size:11px"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td class="px-5 py-10 text-center" style="color:#a3a3a3;font-size:13px">
                            <i class="bi bi-inbox block mb-2" style="font-size:24px"></i>
                            <?= ($pesquisa_id || ($filtro_tipo_ben ?? '') || ($filtro_bem ?? '') || ($filtro_complemento ?? '') || ($filtro_dependencia ?? '') || ($filtro_STATUS ?? ''))
                                ? 'Nenhum produto encontrado com os filtros aplicados.'
                                : 'Nenhum produto cadastrado para esta planilha.' ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="px-5 py-3 border-t border-neutral-100 bg-neutral-50">
        <?php
        echo \App\Helpers\PaginationHelper::render(
            (int) ($pagina ?? 1),
            (int) ($total_paginas ?? 1),
            '/products/view',
            [
                'id' => $comum_id ?? '',
                'comum_id' => $comum_id ?? '',
                'filtro_complemento' => $filtro_complemento ?? '',
                'pesquisa_id' => $pesquisa_id ?? '',
                'filtro_tipo_ben' => $filtro_tipo_ben ?? '',
                'filtro_bem' => $filtro_bem ?? '',
                'filtro_dependencia' => $filtro_dependencia ?? '',
                'filtro_status' => $filtro_STATUS ?? '',
            ]
        );
        ?>
    </div>
</div>

<script src="/assets/js/products/index.js"></script>

<?php
$contentHtml = ob_get_clean();
include $projectRoot . '/src/Views/layouts/app.php';
?>
