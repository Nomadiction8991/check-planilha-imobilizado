<?php


$appConfig = require dirname(__DIR__, 3) . '/config/app.php';
$projectRoot = $appConfig['project_root'];
require_once $projectRoot . '/src/Helpers/BootstrapLoader.php';

// Usar comum_id passado pelo controller
$comum_id = $comum_id ?? 0;

// Dados serão fornecidos pelo controller
$PRODUTOS = $produtos ?? [];
$erro_PRODUTOS = $erro_produtos ?? '';
$filtro_STATUS = $filtro_status ?? '';
$erros_importacao_pendentes = $erros_importacao_pendentes ?? 0;


$id_planilha = $comum_id;
$pageTitle = htmlspecialchars($planilha['comum_descricao'] ?? 'VISUALIZAR Planilha');
$backUrl = base_url('/churches');


if (false && !empty($acesso_bloqueado)) {
    // Bloco legado mantido desativado intencionalmente.
}


ob_start();
?>

<style>
    .card {
        background: #fff;
        border: 1px solid #e5e5e5;
        border-radius: 2px;
        margin-bottom: 16px;
        overflow: hidden;
    }

    .card-header,
    .card-footer {
        background: #fafafa;
        border-color: #e5e5e5;
    }

    .card-header {
        padding: 14px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .08em;
        color: #171717;
        text-transform: uppercase;
    }

    .card-body {
        padding: 18px;
    }

    .card-footer {
        padding: 12px 18px;
        font-size: 12px;
        color: #525252;
    }

    .form-label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: #262626;
        margin-bottom: 6px;
    }

    .form-control,
    .form-select {
        width: 100%;
        min-height: 42px;
        padding: 10px 12px;
        border: 1px solid #d4d4d4;
        border-radius: 2px;
        background: #fff;
        color: #171717;
        font-size: 14px;
    }

    .form-control:focus,
    .form-select:focus {
        outline: none;
        border-color: #000;
        box-shadow: none;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 40px;
        padding: 8px 14px;
        border-radius: 2px;
        border: 1px solid #d4d4d4;
        background: #fff;
        color: #171717;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        transition: background .12s ease, color .12s ease, border-color .12s ease;
    }

    .btn:hover {
        background: #f5f5f5;
        color: #171717;
    }

    .btn.btn-primary,
    .btn-primary {
        background: #000;
        border-color: #000;
        color: #fff;
    }

    .btn.btn-primary:hover,
    .btn-primary:hover {
        background: #171717;
        color: #fff;
    }

    .accordion-button {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 0;
        background: transparent;
        border: none;
        color: #171717;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        box-shadow: none;
    }

    .accordion-body {
        padding: 16px 0 0;
        border-top: 1px solid #e5e5e5;
    }

    .filters-grid {
        display: grid;
        gap: 14px;
    }

    .filters-grid-advanced {
        display: grid;
        gap: 14px;
    }

    .toolbar-inline {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin: 0 0 18px;
    }

    .toolbar-inline .floating-btn {
        min-width: 170px !important;
        height: 40px !important;
        border-radius: 2px !important;
        border: 1px solid #d4d4d4 !important;
        background: #fff !important;
        color: #171717 !important;
        box-shadow: none !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        padding: 0 14px !important;
        justify-content: center !important;
    }

    .toolbar-inline .floating-btn:hover {
        transform: none !important;
        background: #f5f5f5 !important;
        box-shadow: none !important;
    }

    .toolbar-inline .floating-btn.listening {
        background: #000 !important;
        color: #fff !important;
        animation: none !important;
    }

    .floating-buttons-container {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin: 0 0 16px;
        position: static !important;
        align-items: center !important;
    }

    .floating-btn {
        cursor: pointer !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        line-height: 1 !important;
    }

    .list-group {
        display: block;
    }

    .list-group-item {
        display: grid;
        grid-template-columns: 112px minmax(0, 1fr) 176px;
        gap: 16px;
        align-items: start;
        padding: 18px;
        border-top: 1px solid #e5e5e5;
        background: #fff;
    }

    .list-group-item:first-child {
        border-top: none;
    }

    .produto-header {
        display: flex;
        flex-direction: column;
        gap: 8px;
        align-items: flex-start;
    }

    .codigo-PRODUTO {
        font-size: 20px;
        font-weight: 700;
        line-height: 1;
        color: #111827;
        font-family: Monaco, "Courier New", monospace;
    }

    .tag-cadastro-novo {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 8px;
        border: 1px solid #d4d4d4;
        border-radius: 2px;
        font-size: 11px;
        font-weight: 700;
        color: #171717;
        background: #fafafa;
    }

    .info-PRODUTO {
        font-size: 15px;
        line-height: 1.45;
        color: #171717;
    }

    .edicao-pendente,
    .observacao-PRODUTO {
        margin-top: 12px;
        padding: 10px 12px;
        border: 1px solid #e5e5e5;
        border-radius: 2px;
        background: #fafafa;
        font-size: 12px;
        color: #404040;
        line-height: 1.5;
    }

    .acao-container {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }

    .acao-container .btn,
    .acao-container button {
        width: 100%;
        min-height: 42px;
    }

    .acao-container .btn span,
    .acao-container button span {
        white-space: nowrap;
    }

    .acao-container .btn.active,
    .acao-container button.active {
        background: #000;
        border-color: #000;
        color: #fff;
    }

    .linha-dr {
        opacity: .6;
    }

    .linha-checado {
        background: #fcfcfc;
    }

    .linha-imprimir {
        border-left: 3px solid #000;
    }

    .camera-fullscreen-modal,
    .modal.fade {
        display: none;
    }

    .camera-fullscreen-modal.show {
        display: flex;
        position: fixed;
        inset: 0;
        z-index: 1100;
        background: rgba(0, 0, 0, 0.82);
        align-items: center;
        justify-content: center;
        padding: 24px;
    }

    .camera-fullscreen-content {
        width: min(1100px, 100%);
        background: #111;
        border: 1px solid #2a2a2a;
        border-radius: 2px;
        overflow: hidden;
    }

    .camera-scanner-container {
        position: relative;
        min-height: 460px;
        background: #000;
    }

    .camera-close-btn {
        position: absolute;
        top: 18px;
        right: 18px;
        z-index: 2;
        width: 42px;
        height: 42px;
        border: 1px solid #fff;
        background: transparent;
        color: #fff;
        border-radius: 2px;
    }

    .camera-controls {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
        padding: 14px 16px;
        background: #111;
        color: #fff;
        border-top: 1px solid #2a2a2a;
    }

    .camera-controls .form-select,
    .camera-controls .form-range {
        max-width: 220px;
    }

    .modal.fade.show {
        display: flex;
        position: fixed;
        inset: 0;
        z-index: 1110;
        background: rgba(0, 0, 0, 0.45);
        align-items: center;
        justify-content: center;
        padding: 24px;
    }

    .modal-dialog {
        width: min(560px, 100%);
        margin: 0;
    }

    .modal-content {
        background: #fff;
        border: 1px solid #d4d4d4;
        border-radius: 2px;
        overflow: hidden;
    }

    .modal-header,
    .modal-footer {
        padding: 14px 16px;
        background: #fafafa;
        border-color: #e5e5e5;
    }

    .modal-body {
        padding: 16px;
    }

    @media (min-width: 1024px) {
        .filters-grid {
            grid-template-columns: minmax(0, 2fr) 220px;
            align-items: end;
        }

        .filters-grid-advanced {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }

    @media (max-width: 1023px) {
        .floating-buttons-container {
            position: fixed !important;
            right: 16px !important;
            bottom: 90px !important;
            z-index: 1040 !important;
            flex-direction: column !important;
            align-items: flex-end !important;
            margin: 0 !important;
        }

        .floating-buttons-container .floating-btn {
            width: 56px !important;
            height: 56px !important;
            min-width: 56px !important;
            border-radius: 50% !important;
            border-color: #000 !important;
            background: #000 !important;
            color: #fff !important;
        }

        .floating-buttons-container .floating-btn span {
            display: none;
        }

        .list-group-item {
            grid-template-columns: 1fr;
        }

        .acao-container {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .acao-container .btn,
        .acao-container button {
            min-height: 44px;
            padding: 0;
            gap: 0;
        }

        .acao-container .btn span,
        .acao-container button span {
            display: none;
        }
    }
</style>

<!-- Link para Material Icons -->
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">

<?php if (!empty($_GET['sucesso'])): ?>
    <div class="px-4 py-3 mb-3 d-flex align-items-start gap-3" style="background:#fafafa;border:1px solid #d4d4d4;border-radius:2px;color:#171717">
        <i class="bi bi-check-circle flex-shrink-0"></i>
        <div class="flex-grow-1"><?php echo htmlspecialchars(to_uppercase($_GET['sucesso']), ENT_QUOTES, 'UTF-8'); ?></div>
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    </div>
<?php endif; ?>
<?php if (!empty($erro_PRODUTOS)): ?>
    <div class="px-4 py-3 mb-3 d-flex align-items-start gap-3" style="background:#fafafa;border:1px solid #000;border-radius:2px;color:#171717">
        <i class="bi bi-info-circle flex-shrink-0"></i>
        <div class="flex-grow-1">Erro ao carregar PRODUTOS: <?php echo htmlspecialchars($erro_PRODUTOS); ?></div>
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    </div>
<?php endif; ?>

<?php if ($erros_importacao_pendentes > 0): ?>
    <!-- Alerta persistente de erros de importação — não tem botão X, some só quando todos resolvidos -->
    <div class="d-flex align-items-center gap-3 mb-3 px-4 py-3"
        style="background:#fafafa;border:1px solid #000;border-radius:2px;color:#171717;">
        <i class="bi bi-info-circle flex-shrink-0"></i>
        <div class="flex-grow-1">
            <strong>
                <?php echo $erros_importacao_pendentes === 1
                    ? '1 item com erro de importação'
                    : $erros_importacao_pendentes . ' itens com erros de importação'; ?>
            </strong>
            — alguns produtos não foram importados corretamente e precisam de correção.
            <br>
            <a href="/spreadsheets/import-errors?comum_id=<?php echo (int) $comum_id; ?>"
                class="fw-semibold" style="color:#171717;text-decoration:underline">
                <i class="bi bi-arrow-right-circle me-1"></i>Ver erros e baixar CSV para reimportar
            </a>
        </div>
    </div>
<?php endif; ?>

<!-- Filtros -->
<div class="card mb-3">
    <div class="card-header">
        <span><i class="bi bi-funnel me-2"></i><?php echo htmlspecialchars(to_uppercase('Filtros'), ENT_QUOTES, 'UTF-8'); ?></span>
        <span style="font-size:11px;color:#737373;font-weight:600;letter-spacing:.04em"><?php echo (int)($total_registros ?? 0); ?> itens</span>
    </div>
    <div class="card-body">
        <div class="toolbar-inline floating-buttons-container">
            <button id="btnFloatingMic" class="floating-btn mic" type="button" title="Falar código">
                <i class="bi bi-mic-fill"></i>
                <span>Falar código</span>
            </button>
            <button id="btnFloatingCam" class="floating-btn cam" type="button" title="Escanear código de barras">
                <i class="bi bi-camera-video-fill"></i>
                <span>Escanear código</span>
            </button>
        </div>
        <form method="GET" action="">
            <input type="hidden" name="id" value="<?php echo $comum_id; ?>">
            <input type="hidden" name="comum_id" value="<?php echo $comum_id; ?>">

            <div class="filters-grid mb-3">
                <div>
                    <label class="form-label" for="filtro_codigo">
                        <i class="bi bi-upc-scan me-1"></i>
                        <?php echo htmlspecialchars(to_uppercase('Código do Produto'), ENT_QUOTES, 'UTF-8'); ?>
                    </label>
                    <input type="text" class="form-control" id="filtro_codigo" name="filtro_codigo"
                        value="<?php echo htmlspecialchars($filtro_codigo ?? ''); ?>"
                        placeholder="<?php echo htmlspecialchars(to_uppercase('Digite, fale ou escaneie o código...'), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div style="display:flex;align-items:end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-2"></i>
                        <?php echo htmlspecialchars(to_uppercase('Filtrar'), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                </div>
            </div>

            <div class="accordion" id="filtrosAvancados">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button">
                            <i class="bi bi-sliders me-2"></i>
                            <?php echo htmlspecialchars(to_uppercase('Filtros Avançados'), ENT_QUOTES, 'UTF-8'); ?>
                        </button>
                    </h2>
                    <div id="collapseFiltros" class="accordion-collapse">
                        <div class="accordion-body filters-grid-advanced">
                            <div>
                                <label class="form-label" for="nome"><?php echo htmlspecialchars(to_uppercase('Nome'), ENT_QUOTES, 'UTF-8'); ?></label>
                                <input type="text" class="form-control" id="nome" name="nome"
                                    value="<?php echo htmlspecialchars($filtro_nome ?? ''); ?>"
                                    placeholder="<?php echo htmlspecialchars(to_uppercase('Pesquisar nome...'), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>

                            <div>
                                <label class="form-label" for="dependencia"><?php echo htmlspecialchars(to_uppercase('Dependncia'), ENT_QUOTES, 'UTF-8'); ?></label>
                                <select class="form-select" id="dependencia" name="dependencia">
                                    <option value=""><?php echo htmlspecialchars(to_uppercase('Todas'), ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php foreach ($dependencia_options as $dep): ?>
                                        <?php
                                        $depId = (string) ($dep['id'] ?? '');
                                        $depDesc = (string) ($dep['descricao'] ?? $depId);
                                        ?>
                                        <option value="<?php echo htmlspecialchars($depId); ?>"
                                            <?php echo ($filtro_dependencia ?? '') == $depId ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars(to_uppercase($depDesc), ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="form-label" for="status">STATUS</label>
                                <select class="form-select" id="status" name="status">
                                    <option value=""><?php echo htmlspecialchars(to_uppercase('Todos'), ENT_QUOTES, 'UTF-8'); ?></option>
                                    <option value="checado" <?php echo ($filtro_STATUS ?? '') === 'checado' ? 'selected' : ''; ?>><?php echo htmlspecialchars(to_uppercase('Checados'), ENT_QUOTES, 'UTF-8'); ?></option>
                                    <option value="observacao" <?php echo ($filtro_STATUS ?? '') === 'observacao' ? 'selected' : ''; ?>><?php echo htmlspecialchars(to_uppercase('Com observação'), ENT_QUOTES, 'UTF-8'); ?></option>
                                    <option value="etiqueta" <?php echo ($filtro_STATUS ?? '') === 'etiqueta' ? 'selected' : ''; ?>><?php echo htmlspecialchars(to_uppercase('Etiqueta para Imprimir'), ENT_QUOTES, 'UTF-8'); ?></option>
                                    <option value="pendente" <?php echo ($filtro_STATUS ?? '') === 'pendente' ? 'selected' : ''; ?>><?php echo htmlspecialchars(to_uppercase('Pendentes'), ENT_QUOTES, 'UTF-8'); ?></option>
                                    <option value="editado" <?php echo ($filtro_STATUS ?? '') === 'editado' ? 'selected' : ''; ?>><?php echo htmlspecialchars(to_uppercase('Editados'), ENT_QUOTES, 'UTF-8'); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <div class="card-footer small">
        <?php echo htmlspecialchars(to_uppercase(($total_registros ?? 0) . ' registros encontrados no total'), ENT_QUOTES, 'UTF-8'); ?>
    </div>
</div>

<!-- MODAL DE CÂMERA EM TELA INTEIRA -->
<div class="camera-fullscreen-modal" id="cameraFullscreenModal">
    <button class="camera-close-btn" id="cameraCloseBtn" type="button">
        <i class="bi bi-x-lg"></i>
    </button>

    <div class="camera-fullscreen-content">
        <div class="camera-scanner-container" id="cameraFullscreenContainer">
            <div class="camera-overlay">
                <div class="scanner-frame-fullscreen" id="scannerFrameFullscreen"></div>
            </div>
        </div>

        <div class="camera-controls">
            <label for="cameraSelectFloating">Câmera:</label>
            <select id="cameraSelectFloating" class="form-select form-select-sm" style="max-width: 200px;">
                <option value="">Câmera padrão</option>
            </select>

            <label for="zoomSliderFloating" style="flex: 1; max-width: 200px; margin-left: auto;">
                Zoom: <span id="zoomLevelFloating">1.0x</span>
            </label>
            <input type="range" id="zoomSliderFloating" class="form-range" min="1" max="10" step="0.5" value="1" style="max-width: 150px;">
        </div>
    </div>
</div>

<!-- Listagem de PRODUTOS -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-box-seam me-2"></i>
            PRODUTOS
        </span>
    </div>
    <div class="list-group list-group-flush">
        <?php if ($PRODUTOS): ?>
            <?php foreach ($PRODUTOS as $p):

                $classe = '';
                $tem_edicao = $p['editado'] == 1;

                if ($p['ativo'] == 0) {
                    $classe = 'linha-dr';
                } elseif (($p['imprimir_etiqueta'] ?? 0) == 1) {
                    $classe = 'linha-imprimir';
                } elseif ($p['checado'] == 1) {
                    $classe = 'linha-checado';
                } elseif (!empty($p['observacao'])) {
                    $classe = 'linha-observacao';
                } elseif ($tem_edicao) {
                    $classe = 'linha-editado';
                } else {
                    $classe = 'linha-pendente';
                }



                if ($p['ativo'] == 0) {

                    $show_check = false;
                    $show_imprimir = true;
                    $show_obs = true;
                    $show_edit = true;
                } else {

                    $show_check = true;
                    $show_imprimir = true;
                    $show_obs = true;
                    $show_edit = true;
                }

                $checkDisabled = !$show_check;
                $imprimirDisabled = !$show_imprimir;
                $obsDisabled = !$show_obs;
                $editDisabled = !$show_edit;

                $tipo_invalido = (!isset($p['tipo_bem_id']) || $p['tipo_bem_id'] == 0 || empty($p['tipo_bem_id']));
            ?>
                <?php
                $produtoId = $p['id_PRODUTO'] ?? $p['id_produto'] ?? $p['ID_PRODUTO'] ?? ($p['ID_PRODUTO'] ?? '');
                $produtoId = intval($produtoId);
                ?>
                <div
                    class="list-group-item <?php echo $classe; ?><?php echo $tipo_invalido ? ' tipo-nao-identificado' : ''; ?>"
                    data-produto-id="<?php echo $produtoId; ?>"
                    data-ativo="<?php echo (int) $p['ativo']; ?>"
                    data-checado="<?php echo (int) $p['checado']; ?>"
                    data-imprimir="<?php echo (int) ($p['imprimir_etiqueta'] ?? 0); ?>"
                    data-observacao="<?php echo htmlspecialchars($p['observacao'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    data-editado="<?php echo (int) $p['editado']; ?>"
                    <?php echo $tipo_invalido ? 'title="Tipo de bem não identificado"' : ''; ?>>
                    <!-- Header: Código + Tag -->
                    <div class="produto-header">
                        <div class="codigo-PRODUTO" style="margin-bottom: 0;">
                            <?php echo \App\Helpers\ViewHelper::e(\App\Helpers\ViewHelper::formatarCodigoCurto($p['codigo'] ?? '')); ?>
                        </div>
                        <?php if (!empty($p['novo'])): ?>
                            <span class="tag-cadastro-novo">
                                <i class="bi bi-tag-fill"></i> NOVO
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Edição Pendente / Impressão 14.1 -->
                    <?php if ($tem_edicao || ($p['imprimir_14_1'] ?? 0) == 1): ?>
                        <div class="edicao-pendente">
                            <strong><?php echo mb_convert_case('Edição', MB_CASE_TITLE, 'UTF-8') . ':'; ?></strong>

                            <?php if ($tem_edicao): ?>
                                <br>
                                <?php

                                $dep_final = ($p['editado_dependencia_desc'] ?: $p['dependencia_desc']);

                                // Sempre montar a descrição editada a partir das partes
                                $tipo_codigo_final = $p['editado_tipo_codigo'] ?: $p['tipo_codigo'];
                                $tipo_desc_final = $p['editado_tipo_desc'] ?: $p['tipo_desc'];
                                $ben_final = !empty($p['editado_bem']) ? $p['editado_bem'] : ($p['bem'] ?? '');
                                $comp_final = !empty($p['editado_complemento']) ? $p['editado_complemento'] : ($p['complemento'] ?? '');

                                $partes = [];
                                if ($tipo_codigo_final || $tipo_desc_final) {
                                    $partes[] = mb_strtoupper(trim(($tipo_codigo_final ? $tipo_codigo_final . ' - ' : '') . $tipo_desc_final), 'UTF-8');
                                }
                                if ($ben_final !== '') {
                                    $partes[] = mb_strtoupper($ben_final, 'UTF-8');
                                }
                                if ($comp_final !== '') {
                                    $comp_tmp = mb_strtoupper($comp_final, 'UTF-8');
                                    if ($ben_final !== '' && strpos($comp_tmp, strtoupper($ben_final)) === 0) {
                                        $comp_tmp = trim(substr($comp_tmp, strlen($ben_final)));
                                        $comp_tmp = preg_replace('/^[\s\-\/]+/', '', $comp_tmp);
                                    }
                                    if ($comp_tmp !== '') $partes[] = $comp_tmp;
                                }

                                $desc_editada_visivel = implode(' ', $partes);

                                if ($desc_editada_visivel === '') {
                                    $desc_editada_visivel = 'EDICAO SEM DESCRICAO';
                                }

                                // Anexar dependência editada/original entre chaves no fim
                                if (!empty($dep_final)) {
                                    $desc_editada_visivel .= ' {' . mb_strtoupper($dep_final, 'UTF-8') . '}';
                                }

                                echo htmlspecialchars($desc_editada_visivel);
                                ?><br>
                            <?php endif; ?>

                            <?php if (($p['imprimir_14_1'] ?? 0) == 1): ?>
                                <div class="relatorios-lista">
                                    <strong class="relatorios-subtitulo">Relatórios</strong>
                                    <ul>
                                        <li>14.1</li>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <?php
                            // Exibir dados da Nota Fiscal se existirem
                            $nota_numero = trim((string)($p['nota_numero'] ?? ''));
                            $nota_data = trim((string)($p['nota_data'] ?? ''));
                            $nota_valor = trim((string)($p['nota_valor'] ?? ''));
                            $nota_fornecedor = trim((string)($p['nota_fornecedor'] ?? ''));
                            if ($nota_numero !== '' || $nota_data !== '' || $nota_valor !== '' || $nota_fornecedor !== ''): ?>
                                <div class="nota-fiscal-lista mt-2">
                                    <strong>Nota Fiscal:</strong><br>
                                    <?php if ($nota_numero !== ''): ?>Número: <?php echo htmlspecialchars($nota_numero, ENT_QUOTES, 'UTF-8'); ?><br><?php endif; ?>
                                <?php if ($nota_data !== ''): ?>Data: <?php echo htmlspecialchars($nota_data, ENT_QUOTES, 'UTF-8'); ?><br><?php endif; ?>
                            <?php if ($nota_valor !== ''): ?>Valor: <?php echo htmlspecialchars($nota_valor, ENT_QUOTES, 'UTF-8'); ?><br><?php endif; ?>
                        <?php if ($nota_fornecedor !== ''): ?>Fornecedor: <?php echo htmlspecialchars($nota_fornecedor, ENT_QUOTES, 'UTF-8'); ?><br><?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Observacao -->
                    <?php if (!empty($p['observacao'])): ?>
                        <div class="observacao-PRODUTO">
                            <strong><?php echo htmlspecialchars(to_uppercase('observação'), ENT_QUOTES, 'UTF-8'); ?>:</strong><br>
                            <?php echo htmlspecialchars(to_uppercase($p['observacao']), ENT_QUOTES, 'UTF-8'); ?><br>
                        </div>
                    <?php endif; ?>

                    <!-- Informações -->
                    <div class="info-PRODUTO">
                        <?php
                        // Montar título padrão: {TIPO_BEM} BEM COMPLEMENTO {DEPENDÊNCIA}
                        $tipoCodigo = trim((string)($p['tipo_codigo'] ?? ''));
                        $tipoDesc = trim((string)($p['tipo_desc'] ?? ''));

                        $tipoPart = '';
                        if ($tipoCodigo !== '' || $tipoDesc !== '') {
                            $tipoPart = '{' . mb_strtoupper(trim(($tipoCodigo ? $tipoCodigo . ' - ' : '') . $tipoDesc), 'UTF-8') . '}';
                        }

                        // Descrição: bem + complemento dos campos estruturados
                        $bemOriginal  = trim((string)($p['bem'] ?? ''));
                        $compOriginal = trim((string)($p['complemento'] ?? ''));
                        $descricao    = $bemOriginal . ($compOriginal !== '' ? ' ' . $compOriginal : '');

                        // Usar a dependência originalmente importada (dependencia_desc)
                        $depImport = trim((string)($p['dependencia_desc'] ?? ''));
                        $depPart = $depImport !== '' ? ' {' . mb_strtoupper($depImport, 'UTF-8') . '}' : '';

                        $titulo = trim(($tipoPart ? $tipoPart . ' ' : '') . $descricao . ($depPart ? ' ' . $depPart : ''));
                        ?>
                        <strong><?php echo htmlspecialchars($titulo); ?></strong>
                    </div>

                    <!-- Ações -->
                    <?php
                    $paginaAtual = $pagina ?? 1;
                    $filtroNomeParam = urlencode($filtro_nome ?? '');
                    $filtroDependenciaParam = urlencode($filtro_dependencia ?? '');
                    $filtroCodigoParam = urlencode($filtro_codigo ?? '');
                    $filtroStatusParam = urlencode($filtro_STATUS ?? '');
                    $observacaoUrl = '/products/observation?id_produto=' . $produtoId . '&comum_id=' . $comum_id . '&pagina=' . $paginaAtual . '&nome=' . $filtroNomeParam . '&dependencia=' . $filtroDependenciaParam . '&filtro_codigo=' . $filtroCodigoParam . '&status=' . $filtroStatusParam;
                    $editarUrl = '/products/edit?id_produto=' . $produtoId . '&comum_id=' . $comum_id . '&pagina=' . $paginaAtual . '&nome=' . $filtroNomeParam . '&dependencia=' . $filtroDependenciaParam . '&filtro_codigo=' . $filtroCodigoParam . '&status=' . $filtroStatusParam;
                    ?>
                    <div class="acao-container">
                        <!-- Check -->
                        <form method="POST" action="/products/check" class="PRODUTO-action-form action-check" data-action="check" data-produto-id="<?php echo $produtoId; ?>">
                            <input type="hidden" name="produto_id" value="<?php echo $produtoId; ?>">
                            <input type="hidden" name="checado" value="<?php echo $p['checado'] ? '0' : '1'; ?>">
                            <button type="submit" class="btn btn-sm <?php echo $p['checado'] == 1 ? 'active' : ''; ?>" title="<?php echo $p['checado'] ? 'Desmarcar checado' : 'Marcar como checado'; ?>" <?php echo $checkDisabled ? 'disabled' : ''; ?>>
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Checado</span>
                            </button>
                        </form>

                        <!-- Etiqueta -->
                        <form method="POST" action="/products/label" class="PRODUTO-action-form action-imprimir" data-action="imprimir" data-produto-id="<?php echo $produtoId; ?>">
                            <input type="hidden" name="produto_id" value="<?php echo $produtoId; ?>">
                            <input type="hidden" name="imprimir" value="<?php echo ($p['imprimir_etiqueta'] ?? 0) ? '0' : '1'; ?>">
                            <button type="submit" class="btn btn-sm <?php echo ($p['imprimir_etiqueta'] ?? 0) == 1 ? 'active' : ''; ?>" title="Etiqueta" <?php echo $imprimirDisabled ? 'disabled' : ''; ?>>
                                <i class="bi bi-tag-fill"></i>
                                <span>Etiqueta</span>
                            </button>
                        </form>

                        <!-- Observacao -->
                        <a href="<?php echo $obsDisabled ? '#' : $observacaoUrl; ?>"
                            class="btn btn-sm action-observacao <?php echo !empty($p['observacao']) ? 'active' : ''; ?> <?php echo $obsDisabled ? 'disabled' : ''; ?>"
                            data-produto-id="<?php echo $produtoId; ?>"
                            data-comum-id="<?php echo htmlspecialchars($comum_id ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            title="<?php echo htmlspecialchars(to_uppercase('observação'), ENT_QUOTES, 'UTF-8'); ?>"
                            <?php if ($obsDisabled): ?>tabindex="-1" aria-disabled="true" onclick="event.preventDefault();" <?php endif; ?>>
                            <i class="bi bi-chat-square-text-fill"></i>
                            <span>Observação</span>
                        </a>

                        <!-- EDITAR -->
                        <a href="<?php echo $editDisabled ? '#' : $editarUrl; ?>"
                            class="btn btn-sm action-editar <?php echo $tem_edicao ? 'active' : ''; ?> <?php echo $editDisabled ? 'disabled' : ''; ?>"
                            title="EDITAR"
                            <?php if ($editDisabled): ?>tabindex="-1" aria-disabled="true" onclick="event.preventDefault();" <?php endif; ?>>
                            <i class="bi bi-pencil-fill"></i>
                            <span>Editar</span>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="list-group-item text-center py-4" style="grid-template-columns:1fr">
                <i class="bi bi-inbox d-block mb-2"></i>
                <span class="text-muted">Nenhum PRODUTO encontrado</span>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Paginação -->
<?php
echo \App\Helpers\PaginationHelper::render(
    (int) ($pagina ?? 1),
    (int) ($total_paginas ?? 1),
    '/products/view',
    [
        'id' => $comum_id ?? '',
        'comum_id' => $comum_id ?? '',
        'nome' => $filtro_nome ?? '',
        'dependencia' => $filtro_dependencia ?? '',
        'filtro_codigo' => $filtro_codigo ?? '',
        'status' => $filtro_STATUS ?? '',
    ]
);
?>

<!-- Variáveis PHP necessárias para o JS externo -->
<script>
    window._comumId = <?php echo json_encode($comum_id ?? ''); ?>;
</script>

<!-- Modal para escanear código de barras -->
<div class="modal fade" id="barcodeModal" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-fullscreen-custom">
        <div class="modal-content bg-dark">
            <div class="modal-body p-0 position-relative">
                <div id="scanner-container" style="width:100%; height:100%; background:#000; position:relative; overflow:hidden;"></div>

                <!-- Botão X para fechar -->
                <button type="button" class="btn-close-scanner">
                    <i class="bi bi-x-lg"></i>
                </button>

                <!-- Controles de câmera e zoom -->
                <div class="scanner-controls">
                    <select id="cameraSelect" class="form-select form-select-sm">
                        <option value="">Carregando câmeras...</option>
                    </select>
                    <div class="zoom-control">
                        <i class="bi bi-zoom-out"></i>
                        <input type="range" id="zoomSlider" min="1" max="3" step="0.1" value="1" class="form-range">
                        <i class="bi bi-zoom-in"></i>
                    </div>
                </div>

                <!-- Overlay com moldura e dica -->
                <div class="scanner-overlay">
                    <div class="scanner-frame"></div>
                    <div class="scanner-hint">Posicione o código de barras dentro da moldura</div>
                    <div class="scanner-info" id="scannerInfo">Inicializando câmera...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Observação (edição rpida via AJAX) -->
<div class="modal fade" id="observacaoModal" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Observação</h5>
                <button type="button" class="btn-close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label for="observacaoText" class="form-label">Observação</label>
                    <textarea id="observacaoText" class="form-control" rows="4" placeholder="Digite a observação..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn">Cancelar</button>
                <button type="button" class="btn btn-primary" id="observacaoSaveBtn">Salvar</button>
            </div>
        </div>
    </div>
</div>



<!-- Quagga2 para leitura de códigos de barras -->
<script src="https://unpkg.com/@ericblade/quagga2/dist/quagga.min.js"></script>
<!-- JS extraído: AJAX, voz, câmera/barcode -->
<script src="/assets/js/spreadsheets/view.js"></script>

<?php

$contentHtml = ob_get_clean();

include $projectRoot . '/src/Views/layouts/app.php';
?>
