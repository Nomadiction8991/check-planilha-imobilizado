<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editor de Células — {{ $formulario }}</title>
    @include('partials.pwa')
    <link rel="stylesheet" href="{{ asset('assets/reports/secao14-templates.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/reports/report-cell-editor.css') }}">
    @include('partials.design-tokens')
    <style>
        body { font-family: system-ui, sans-serif; font-size: 16px; line-height: 1.5; }
        #topbar { background: var(--ui-surface); color: var(--ui-text); border-color: var(--ui-border); }
        #topbar h1 { color: var(--ui-text); font-size: 24px; }
        button, #topbar a, input[type="text"], input[type="number"] { min-height: 44px; font-size: 16px; }
        button { border-radius: var(--radius-sm); }
        button:focus-visible, a:focus-visible, input:focus-visible, [tabindex]:focus-visible { outline: 3px solid var(--ui-focus); outline-offset: 2px; }
        #topbar button, #topbar a { display: inline-flex; align-items: center; justify-content: center; }
        #topbar a { color: var(--ui-text) !important; border: 1px solid var(--ui-border) !important; border-radius: var(--radius-sm) !important; font-size: 16px !important; }
        #topbar a:hover { background: var(--ui-surface-muted); }
        #topbar #btn-save { background: var(--product-primary); border-color: var(--product-primary); color: var(--product-primary-text); }
        #topbar #btn-save:hover { background: var(--product-primary-hover); }
        #topbar #btn-save:disabled, button:disabled { opacity: .6; cursor: not-allowed; }
        #topbar .tb-label, #zoom-display, #mouse-display { color: var(--ui-text-muted); font-size: 16px; }
        #sidebar, #log-panel, #log-header { background: var(--ui-surface); color: var(--ui-text); border-color: var(--ui-border); }
        #canvas-wrapper { background: var(--ui-surface-muted); }
        .sb-section { border-color: var(--ui-border); }
        .sb-title, .coord-field label { color: var(--ui-text-muted); font-size: 16px; }
        #sidebar input[type="text"] { background: var(--ui-surface); color: var(--ui-text); border-color: var(--ui-border); font-size: 16px; }
        #no-selection, #no-selection span { color: var(--ui-text-muted) !important; font-size: 16px !important; }
        #log-header { width: 100%; min-height: 44px; font-size: 16px; border: 0; border-bottom: 1px solid var(--ui-border); border-radius: 0; text-align: left; }
        #log-header:hover { background: var(--ui-surface-muted); }
        #log-positions, #log-code { color: var(--ui-text); }
        @media (max-width: 639px) {
            body { height: auto; min-height: 100vh; overflow: auto; }
            #topbar { height: auto; flex-wrap: wrap; gap: 8px; }
            #topbar h1 { white-space: normal; width: 100%; }
            #topbar .tb-group { flex-wrap: wrap; }
            #topbar .sep { display: none; }
            #main { min-width: 0; display: flex; flex-direction: column; }
            #canvas-wrapper { min-height: 55vh; }
            #sidebar, #log-panel { width: 100%; max-height: none; }
        }
    </style>
</head>
<body>

<!-- Topbar -->
<div id="topbar">
    <h1>📐 Cell Editor — {{ $formulario }}</h1>

    <div class="sep"></div>

    <div class="tb-group">
        <button id="btn-mode-cell" class="mode-btn active" title="Desenhar célula única">⬜ Célula</button>
        <button id="btn-mode-grid" class="mode-btn" title="Desenhar grade com linhas e colunas">⊞ Grade</button>
    </div>

    <div class="sep"></div>

    <div class="tb-group" id="cell-mode-controls">
        <label class="tb-label" for="cell-name-input">Nome:</label>
        <input type="text" id="cell-name-input" placeholder="ex: administracao">
        <span class="tb-label" style="font-size:10px;color:var(--ui-text-muted)">(desenhe e pressione Enter)</span>
    </div>

    <div class="tb-group" id="grid-mode-controls" style="display:none;">
        <span class="tb-label" style="color:var(--product-primary);">Desenhe a área da grade no formulário →</span>
    </div>

    <div class="sep"></div>

    <div class="tb-group">
        <button id="btn-zoom-out" aria-label="Diminuir zoom">−</button>
        <span id="zoom-display">100%</span>
        <button id="btn-zoom-in" aria-label="Aumentar zoom">+</button>
        <button id="btn-zoom-fit" aria-label="Ajustar zoom à tela">Fit</button>
        <button id="btn-zoom-100" aria-label="Zoom em tamanho real">1:1</button>
    </div>

    <div class="sep"></div>

    <div class="tb-group">
        <button id="btn-detect" style="background:var(--ui-surface);border-color:var(--ui-border);color:var(--ui-text);">🔍 Detectar Campos</button>
        <button id="btn-docs" style="background:var(--ui-surface);border-color:var(--ui-border);color:var(--ui-text);">📂 Documentos</button>
        <button class="btn-save"  id="btn-save">💾 Salvar</button>
        <button class="btn-green" id="btn-copy">⬇ Copiar Código</button>
        <button class="btn-red"   id="btn-clear-all">✕ Limpar Tudo</button>
        <span id="save-indicator" style="font-size:10px;color:var(--ui-text-muted);"></span>
        <a href="{{ $editorBackUrl }}"
           style="color:var(--ui-text-muted);font-size:12px;text-decoration:none;padding:5px 10px;border:1px solid var(--ui-border);border-radius:3px;">
            ← Voltar
        </a>
    </div>

    <div class="sep"></div>

    <div class="tb-group">
        <span class="tb-label">Mouse:</span>
        <span id="mouse-display">x:— y:— w:— h:—</span>
    </div>
</div>

<!-- Main -->
<div id="main">

    <!-- Canvas -->
    <div id="canvas-wrapper">
        <div id="canvas-scaler">
            <div id="canvas-container">
                <img id="form-image" src="{{ $bgUrl }}" alt="Formulário" draggable="false">
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div id="sidebar">
        <div class="sb-section">
            <div class="sb-title" id="sel-title">Seleção</div>

            <!-- Nenhuma seleção -->
            <div id="no-selection" style="font-size:11px;color:var(--ui-text-muted);text-align:center;padding:8px;">
                Clique numa célula<br>
                <span style="color:var(--ui-text-muted);">Ctrl+Clique para múltiplas</span>
            </div>

            <!-- Seleção única -->
            <div id="coord-editor" style="display:none;">
                <div class="coords-grid" style="margin-bottom:8px;">
                    <div class="coord-field">
                        <label for="ed-left">Left (px)</label>
                        <input type="text" id="ed-left">
                    </div>
                    <div class="coord-field">
                        <label for="ed-top">Top (px)</label>
                        <input type="text" id="ed-top">
                    </div>
                    <div class="coord-field">
                        <label for="ed-width">Width (px)</label>
                        <input type="text" id="ed-width">
                    </div>
                    <div class="coord-field">
                        <label for="ed-height">Height (px)</label>
                        <input type="text" id="ed-height">
                    </div>
                </div>
                <div class="coord-field" style="margin-bottom:8px;">
                    <label for="ed-name">Nome</label>
                    <input type="text" id="ed-name">
                </div>
                <button class="btn-red btn-sm" id="btn-delete" style="width:100%;font-size:11px;">Deletar célula</button>
            </div>

            <!-- Multi-seleção -->
            <div id="multi-editor" style="display:none;">
                <div style="font-size:11px;color:var(--ui-warning);margin-bottom:10px;text-align:center;">
                    <span id="multi-count">0</span> células selecionadas
                </div>

                <div style="font-size:10px;color:var(--ui-text-muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.06em;">Definir tamanho igual</div>
                <div class="coords-grid" style="margin-bottom:8px;">
                    <div class="coord-field">
                        <label for="multi-width">Width (px)</label>
                        <input type="text" id="multi-width" placeholder="—">
                    </div>
                    <div class="coord-field">
                        <label for="multi-height">Height (px)</label>
                        <input type="text" id="multi-height" placeholder="—">
                    </div>
                </div>
                <button class="btn-blue btn-sm" id="btn-multi-apply-size" style="width:100%;margin-bottom:8px;font-size:11px;">Aplicar tamanho</button>

                <div style="font-size:10px;color:var(--ui-text-muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.06em;">Ajuste relativo</div>
                <div class="coords-grid" style="margin-bottom:8px;">
                    <div class="coord-field">
                        <label for="multi-dw">Δ Width</label>
                        <input type="text" id="multi-dw" placeholder="ex: +10 ou -5">
                    </div>
                    <div class="coord-field">
                        <label for="multi-dh">Δ Height</label>
                        <input type="text" id="multi-dh" placeholder="ex: +10 ou -5">
                    </div>
                </div>
                <button class="btn-blue btn-sm" id="btn-multi-apply-delta" style="width:100%;margin-bottom:8px;font-size:11px;">Aplicar delta</button>

                <button class="btn-red btn-sm" id="btn-multi-delete" style="width:100%;font-size:11px;">Deletar selecionadas</button>
            </div>
        </div>

        <div class="sb-section" style="flex:1;">
            <div class="sb-title">Células (<span id="cell-count">0</span>)</div>
            <div id="cells-list"></div>
        </div>
    </div>

    <!-- Log / Output -->
    <div id="log-panel">
        <button type="button" id="log-header" aria-expanded="true" aria-controls="log-body">
            <span>📋 Registro de posições</span>
            <span id="log-toggle-btn">▼ recolher</span>
        </button>
        <div id="log-body">
            <div>
                <div style="font-size:10px;color:var(--ui-text-muted);margin-bottom:4px;">Coordenadas (px da imagem)</div>
                <div id="log-positions">Nenhuma célula ainda...</div>
            </div>
            <div>
                <div style="font-size:10px;color:var(--ui-text-muted);margin-bottom:4px;">Código HTML</div>
                <div id="log-code">Nenhuma célula ainda...</div>
            </div>
        </div>
    </div>

</div>

<!-- Modal de Documentos -->
<div id="docs-modal-backdrop">
    <div id="docs-modal">
        <div id="docs-modal-header">
            <h2>📂 Documentos Salvos</h2>
            <button id="btn-docs-close" aria-label="Fechar documentos">✕</button>
        </div>
        <div id="docs-list">
            <div id="docs-empty">Nenhum documento salvo ainda.</div>
        </div>
    </div>
</div>

<!-- Modal de Grade -->
<div id="grid-modal-backdrop">
    <div id="grid-modal">
        <h2>⊞ Configurar Grade</h2>

        <div class="grid-controls">
            <label for="grid-rows">Linhas</label>
            <input type="number" id="grid-rows" value="2" min="1" max="100">
            <label for="grid-cols">Colunas</label>
            <input type="number" id="grid-cols" value="3" min="1" max="50">
            <button id="btn-grid-apply" class="btn-blue" style="padding:5px 12px;">Gerar Tabela</button>
        </div>
        <div class="grid-flags">
            <label>
                <input type="checkbox" id="grid-enable-col-prefix" checked>
                Usar prefixo de coluna
            </label>
            <label>
                <input type="checkbox" id="grid-enable-row-prefix" checked>
                Usar prefixo de linha
            </label>
            <label>
                <input type="checkbox" id="grid-enable-row-number" checked>
                Usar número da linha
            </label>
            <label>
                <input type="checkbox" id="grid-enable-col-number" checked>
                Usar número da coluna
            </label>
        </div>
        <div style="padding:4px 0;font-size:10px;color:var(--ui-text-muted);">
            Ambos: <span style="color:var(--product-primary);">pfx_col</span><span style="color:var(--ui-text-muted);">_</span><span style="color:var(--ui-success);">pfx_lin</span><span style="color:var(--ui-text-muted);">_L_C</span>
            &nbsp;·&nbsp; só coluna: <code style="color:var(--product-primary);">pfx_L</code>
            &nbsp;·&nbsp; só linha: <code style="color:var(--ui-success);">pfx_C</code>
        </div>

        <div id="grid-name-wrap">
            <div class="sb-title" style="margin-bottom:6px;">Nomes das células (linha × coluna)</div>
            <div style="overflow:auto;max-height:320px;border:1px solid var(--ui-border);border-radius:3px;">
                <table id="grid-name-table" style="border-collapse:collapse;width:100%;"></table>
            </div>
        </div>

        <div id="grid-preview-wrap">
            <p>Pré-visualização da grade</p>
            <div id="grid-preview"></div>
        </div>

        <div class="modal-actions">
            <button id="btn-grid-cancel" style="background:var(--ui-surface);color:var(--ui-text);">Cancelar</button>
            <button id="btn-grid-confirm" class="btn-green">✓ Criar Células</button>
        </div>
    </div>
</div>

<!-- Modal de Detecção de Células -->
<div id="detect-modal-backdrop">
    <div id="detect-modal">
        <div id="detect-modal-header">
            <h2>🔍 Detectar Células Automaticamente</h2>
            <button id="btn-detect-close" aria-label="Fechar detecção de células">✕</button>
        </div>
        <div id="detect-progress-area">
            <div id="detect-status-text">Pronto para iniciar.</div>
            <div id="detect-progress-bar-wrap">
                <div id="detect-progress-bar"></div>
            </div>
        </div>
        <div id="detect-results-list">
            <div class="detect-empty" id="detect-empty-msg">
                Clique em <strong>▶ Detectar Campos</strong> para analisar o formulário.<br>
                <span style="font-size:10px;color:var(--ui-text-muted);margin-top:6px;display:block;">
                    O sistema analisa os pixels da imagem para encontrar os campos em branco.
                </span>
            </div>
        </div>
        <div id="detect-modal-footer">
            <span id="detect-summary"></span>
            <div style="display:flex;gap:8px;align-items:center;">
                <label style="font-size:11px;color:var(--ui-text-muted);display:flex;align-items:center;gap:5px;cursor:pointer;">
                    <input type="checkbox" id="detect-with-ocr" style="width:auto;margin:0;">
                    Nomear com OCR (lento)
                </label>
                <button id="btn-detect-apply-all" class="btn-green" style="display:none;">✚ Criar Células Aceitas</button>
                <button id="btn-detect-run" style="background:var(--ui-surface);border-color:var(--ui-border);color:var(--ui-text);">▶ Detectar Campos</button>
            </div>
        </div>
    </div>
</div>

<!-- Interact.js -->
<script src="{{ asset('assets/vendor/interact.min.js') }}"></script>

<script>
    window.reportCellEditorConfig = {
        cellEditorBaseUrl: {{ \Illuminate\Support\Js::from($cellEditorBaseUrl) }},
        cellEditorQuerySuffix: {{ \Illuminate\Support\Js::from($cellEditorQuerySuffix) }},
        currentForm: {{ \Illuminate\Support\Js::from($formulario) }},
    };
</script>
<script src="{{ asset('assets/reports/report-cell-editor.js') }}"></script>
<script>
    const logHeader = document.getElementById('log-header');
    const logBody = document.getElementById('log-body');
    logHeader.addEventListener('click', () => {
        logHeader.setAttribute('aria-expanded', String(logBody.style.display !== 'none'));
        if (logBody.style.display === 'none') {
            document.getElementById('log-panel').style.height = '44px';
        }
    });
</script>
</body>
</html>
