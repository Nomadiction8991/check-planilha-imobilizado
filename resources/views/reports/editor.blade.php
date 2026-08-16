<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editor de Células — {{ $formulario }}</title>
    @include('partials.pwa')
    <link rel="stylesheet" href="{{ asset('assets/reports/secao14-templates.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/reports/report-cell-editor.css') }}">
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
        <span class="tb-label">Nome:</span>
        <input type="text" id="cell-name-input" placeholder="ex: administracao">
        <span class="tb-label" style="font-size:10px;color:#666">(desenhe e pressione Enter)</span>
    </div>

    <div class="tb-group" id="grid-mode-controls" style="display:none;">
        <span class="tb-label" style="color:#4fc3f7;">Desenhe a área da grade no formulário →</span>
    </div>

    <div class="sep"></div>

    <div class="tb-group">
        <button id="btn-zoom-out">−</button>
        <span id="zoom-display">100%</span>
        <button id="btn-zoom-in">+</button>
        <button id="btn-zoom-fit">Fit</button>
        <button id="btn-zoom-100">1:1</button>
    </div>

    <div class="sep"></div>

    <div class="tb-group">
        <button id="btn-detect" style="background:#4a1f6e;border-color:#7b3fa0;">🔍 Detectar Campos</button>
        <button id="btn-docs" style="background:#37474f;border-color:#546e7a;">📂 Documentos</button>
        <button class="btn-save"  id="btn-save">💾 Salvar</button>
        <button class="btn-green" id="btn-copy">⬇ Copiar Código</button>
        <button class="btn-red"   id="btn-clear-all">✕ Limpar Tudo</button>
        <span id="save-indicator" style="font-size:10px;color:#666;"></span>
        <a href="{{ $editorBackUrl }}"
           style="color:#aaa;font-size:12px;text-decoration:none;padding:5px 10px;border:1px solid #555;border-radius:3px;">
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
            <div id="no-selection" style="font-size:11px;color:#666;text-align:center;padding:8px;">
                Clique numa célula<br>
                <span style="color:#555;">Ctrl+Clique para múltiplas</span>
            </div>

            <!-- Seleção única -->
            <div id="coord-editor" style="display:none;">
                <div class="coords-grid" style="margin-bottom:8px;">
                    <div class="coord-field">
                        <label>Left (px)</label>
                        <input type="text" id="ed-left">
                    </div>
                    <div class="coord-field">
                        <label>Top (px)</label>
                        <input type="text" id="ed-top">
                    </div>
                    <div class="coord-field">
                        <label>Width (px)</label>
                        <input type="text" id="ed-width">
                    </div>
                    <div class="coord-field">
                        <label>Height (px)</label>
                        <input type="text" id="ed-height">
                    </div>
                </div>
                <div class="coord-field" style="margin-bottom:8px;">
                    <label>Nome</label>
                    <input type="text" id="ed-name">
                </div>
                <button class="btn-red btn-sm" id="btn-delete" style="width:100%;font-size:11px;">Deletar célula</button>
            </div>

            <!-- Multi-seleção -->
            <div id="multi-editor" style="display:none;">
                <div style="font-size:11px;color:#ffd54f;margin-bottom:10px;text-align:center;">
                    <span id="multi-count">0</span> células selecionadas
                </div>

                <div style="font-size:10px;color:#888;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.06em;">Definir tamanho igual</div>
                <div class="coords-grid" style="margin-bottom:8px;">
                    <div class="coord-field">
                        <label>Width (px)</label>
                        <input type="text" id="multi-width" placeholder="—">
                    </div>
                    <div class="coord-field">
                        <label>Height (px)</label>
                        <input type="text" id="multi-height" placeholder="—">
                    </div>
                </div>
                <button class="btn-blue btn-sm" id="btn-multi-apply-size" style="width:100%;margin-bottom:8px;font-size:11px;">Aplicar tamanho</button>

                <div style="font-size:10px;color:#888;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.06em;">Ajuste relativo</div>
                <div class="coords-grid" style="margin-bottom:8px;">
                    <div class="coord-field">
                        <label>Δ Width</label>
                        <input type="text" id="multi-dw" placeholder="ex: +10 ou -5">
                    </div>
                    <div class="coord-field">
                        <label>Δ Height</label>
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
        <div id="log-header">
            <span>📋 Log de Posições</span>
            <span id="log-toggle-btn">▼ recolher</span>
        </div>
        <div id="log-body">
            <div>
                <div style="font-size:10px;color:#666;margin-bottom:4px;">Coordenadas (px da imagem)</div>
                <div id="log-positions">Nenhuma célula ainda...</div>
            </div>
            <div>
                <div style="font-size:10px;color:#666;margin-bottom:4px;">Código HTML</div>
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
            <button id="btn-docs-close">✕</button>
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
            <label>Linhas</label>
            <input type="number" id="grid-rows" value="2" min="1" max="100">
            <label>Colunas</label>
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
        <div style="padding:4px 0;font-size:10px;color:#555;">
            Ambos: <span style="color:#b06be0;">pfx_col</span><span style="color:#888;">_</span><span style="color:#69f0ae;">pfx_lin</span><span style="color:#888;">_L_C</span>
            &nbsp;·&nbsp; só coluna: <code style="color:#b06be0;">pfx_L</code>
            &nbsp;·&nbsp; só linha: <code style="color:#69f0ae;">pfx_C</code>
        </div>

        <div id="grid-name-wrap">
            <div class="sb-title" style="margin-bottom:6px;">Nomes das células (linha × coluna)</div>
            <div style="overflow:auto;max-height:320px;border:1px solid #3c3c3c;border-radius:3px;">
                <table id="grid-name-table" style="border-collapse:collapse;width:100%;"></table>
            </div>
        </div>

        <div id="grid-preview-wrap">
            <p>Pré-visualização da grade</p>
            <div id="grid-preview"></div>
        </div>

        <div class="modal-actions">
            <button id="btn-grid-cancel" style="background:#3c3c3c;">Cancelar</button>
            <button id="btn-grid-confirm" class="btn-green">✓ Criar Células</button>
        </div>
    </div>
</div>

<!-- Modal de Detecção de Células -->
<div id="detect-modal-backdrop">
    <div id="detect-modal">
        <div id="detect-modal-header">
            <h2>🔍 Detectar Células Automaticamente</h2>
            <button id="btn-detect-close">✕</button>
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
                <span style="font-size:10px;color:#555;margin-top:6px;display:block;">
                    O sistema analisa os pixels da imagem para encontrar os campos em branco.
                </span>
            </div>
        </div>
        <div id="detect-modal-footer">
            <span id="detect-summary"></span>
            <div style="display:flex;gap:8px;align-items:center;">
                <label style="font-size:11px;color:#888;display:flex;align-items:center;gap:5px;cursor:pointer;">
                    <input type="checkbox" id="detect-with-ocr" style="width:auto;margin:0;">
                    Nomear com OCR (lento)
                </label>
                <button id="btn-detect-apply-all" class="btn-green" style="display:none;">✚ Criar Células Aceitas</button>
                <button id="btn-detect-run" style="background:#4a1f6e;border-color:#7b3fa0;color:#fff;">▶ Detectar Campos</button>
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
</body>
</html>
