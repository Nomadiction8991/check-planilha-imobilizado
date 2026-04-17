'use strict';

const { cellEditorBaseUrl: CELL_EDITOR_BASE_URL, cellEditorQuerySuffix: CELL_EDITOR_QUERY_SUFFIX, currentForm: CURRENT_FORM } = window.reportCellEditorConfig || {};

// ─────────────────────────────────────────
// Estado
// ─────────────────────────────────────────
const IMG_W = 1654;  // largura natural da imagem (px)
const IMG_H = 2339;  // altura natural da imagem (px)

const state = {
    cells: [],
    selected: null,       // célula principal (seleção única)
    multiSelection: new Set(), // ids das células em multi-seleção
    zoom: 1,
    drawing: false,
    drawStart: { x: 0, y: 0 },
    tempBox: null,
};

// ─────────────────────────────────────────
// Referências DOM
// ─────────────────────────────────────────
const canvasEl   = document.getElementById('canvas-container');
const imgEl      = document.getElementById('form-image');
const wrapper    = document.getElementById('canvas-wrapper');
const zoomDisp   = document.getElementById('zoom-display');
const cellsList  = document.getElementById('cells-list');
const cellCount  = document.getElementById('cell-count');
const noSel      = document.getElementById('no-selection');
const coordEd    = document.getElementById('coord-editor');
const logPos     = document.getElementById('log-positions');
const logCode    = document.getElementById('log-code');
const mouseDisp  = document.getElementById('mouse-display');

// ─────────────────────────────────────────
// Zoom
// ─────────────────────────────────────────
function setZoom(z) {
    state.zoom = Math.max(0.2, Math.min(4, z));
    canvasEl.style.transform = `scale(${state.zoom})`;
    zoomDisp.textContent = Math.round(state.zoom * 100) + '%';
}

function fitZoom() {
    const wr = wrapper.getBoundingClientRect();
    setZoom(Math.min((wr.width - 48) / IMG_W, (wr.height - 48) / IMG_H));
}

document.getElementById('btn-zoom-in') .addEventListener('click', () => setZoom(state.zoom + 0.1));
document.getElementById('btn-zoom-out').addEventListener('click', () => setZoom(state.zoom - 0.1));
document.getElementById('btn-zoom-100').addEventListener('click', () => setZoom(1));
document.getElementById('btn-zoom-fit').addEventListener('click', fitZoom);

wrapper.addEventListener('wheel', (e) => {
    if (!e.ctrlKey) return;
    e.preventDefault();
    setZoom(state.zoom + (e.deltaY < 0 ? 0.1 : -0.1));
}, { passive: false });

// ─────────────────────────────────────────
// Conversão de coordenadas
// ─────────────────────────────────────────
// Tudo que é salvo em cell.x/y/w/h é em pixels da imagem natural (1654×2339).
// A imagem exibida tem o mesmo tamanho natural — o zoom é feito via CSS transform.
// getBoundingClientRect() retorna a posição VISUAL (pós-transform).
// Para converter mouse → pixels da imagem:
//   imgX = (clientX - canvasRect.left) / zoom
//   imgY = (clientY - canvasRect.top)  / zoom

function clientToImg(clientX, clientY) {
    const r = canvasEl.getBoundingClientRect();
    return {
        x: Math.round((clientX - r.left) / state.zoom),
        y: Math.round((clientY - r.top)  / state.zoom),
    };
}

// ─────────────────────────────────────────
// Desenho (mousedown → mousemove → mouseup)
// ─────────────────────────────────────────
canvasEl.addEventListener('mousedown', (e) => {
    // Não iniciar se clicar em célula existente
    if (e.target !== canvasEl && e.target !== imgEl) return;
    if (e.button !== 0) return;

    const pos = clientToImg(e.clientX, e.clientY);
    state.drawing = true;
    state.drawStart = pos;

    state.tempBox = document.createElement('div');
    state.tempBox.className = 'cell-box';
    state.tempBox.style.cssText = `left:${pos.x}px;top:${pos.y}px;width:1px;height:1px;pointer-events:none;`;
    canvasEl.appendChild(state.tempBox);

    e.preventDefault();
});

document.addEventListener('mousemove', (e) => {
    if (canvasEl.contains(e.target) || e.target === imgEl || e.target === canvasEl) {
        const pos = clientToImg(e.clientX, e.clientY);
        if (state.drawing && state.tempBox) {
            const x = parseInt(state.tempBox.style.left) || 0;
            const y = parseInt(state.tempBox.style.top) || 0;
            const w = parseInt(state.tempBox.style.width) || 0;
            const h = parseInt(state.tempBox.style.height) || 0;
            mouseDisp.textContent = `x:${pos.x} y:${pos.y} w:${w} h:${h}`;
        } else {
            mouseDisp.textContent = `x:${pos.x} y:${pos.y} w:— h:—`;
        }
    }

    if (!state.drawing || !state.tempBox) return;

    const pos = clientToImg(e.clientX, e.clientY);
    const x = Math.min(state.drawStart.x, pos.x);
    const y = Math.min(state.drawStart.y, pos.y);
    const w = Math.abs(pos.x - state.drawStart.x);
    const h = Math.abs(pos.y - state.drawStart.y);

    state.tempBox.style.left   = x + 'px';
    state.tempBox.style.top    = y + 'px';
    state.tempBox.style.width  = w + 'px';
    state.tempBox.style.height = h + 'px';
});

document.addEventListener('mouseup', (e) => {
    if (!state.drawing || !state.tempBox) return;
    state.drawing = false;

    const x = parseInt(state.tempBox.style.left);
    const y = parseInt(state.tempBox.style.top);
    const w = parseInt(state.tempBox.style.width);
    const h = parseInt(state.tempBox.style.height);

    state.tempBox.remove();
    state.tempBox = null;

    if (w < 8 || h < 8) return;

    // Modo grade → abre modal
    if (mode === 'grid') {
        openGridModal({ x, y, w, h });
        return;
    }

    // Modo célula → cria diretamente
    const name = document.getElementById('cell-name-input').value.trim();
    if (!name) {
        document.getElementById('cell-name-input').focus();
        document.getElementById('cell-name-input').style.borderColor = '#f44336';
        setTimeout(() => document.getElementById('cell-name-input').style.borderColor = '', 1000);
        return;
    }

    addCell(name, x, y, w, h);
    document.getElementById('cell-name-input').value = '';
    document.getElementById('cell-name-input').focus();
});

// Enter no input de nome foca para o canvas
document.getElementById('cell-name-input').addEventListener('keydown', (e) => {
    if (e.key === 'Escape') canvasEl.focus();
});

// ─────────────────────────────────────────
// Adicionar / Renderizar célula
// ─────────────────────────────────────────
function addCell(name, x, y, w, h) {
    const id = 'c' + Date.now() + '_' + Math.random().toString(36).slice(2, 7);
    const cell = { id, name, x, y, w, h };
    state.cells.push(cell);
    mountCell(cell);
    selectCell(cell);
    refreshSidebar();
    refreshLog();
    markDirty();
}

function mountCell(cell) {
    const box = document.createElement('div');
    box.className = 'cell-box';
    box.id = cell.id;
    applyCellPos(box, cell);

    // Label
    const lbl = document.createElement('div');
    lbl.className = 'cell-label';
    lbl.textContent = cell.name;
    box.appendChild(lbl);

    // Handle de mover (área central)
    const mv = document.createElement('div');
    mv.className = 'move-handle';
    box.appendChild(mv);

    // Handles de resize
    ['nw','n','ne','e','se','s','sw','w'].forEach(dir => {
        const rh = document.createElement('div');
        rh.className = `rh rh-${dir}`;
        rh.dataset.dir = dir;
        box.appendChild(rh);
    });

    // Selecionar ao clicar — suporta Ctrl para multi-seleção
    box.addEventListener('mousedown', (e) => {
        e.stopPropagation();
        if (e.ctrlKey || e.metaKey) {
            toggleMultiSelect(cell);
        } else {
            // Se clicou em uma já selecionada (multi), mantém a multi-seleção para o drag
            if (!state.multiSelection.has(cell.id)) {
                clearMultiSelection();
                selectCell(cell);
            }
        }
    });

    // ── Interact.js: drag + resize ──
    interact(box)
        .draggable({
            allowFrom: '.move-handle',
            listeners: {
                move(ev) {
                    const dx = Math.round(ev.dx / state.zoom);
                    const dy = Math.round(ev.dy / state.zoom);

                    // Se tem multi-seleção, move todas; senão move só esta
                    const targets = state.multiSelection.size > 0
                        ? state.cells.filter(c => state.multiSelection.has(c.id))
                        : [cell];

                    targets.forEach(c => {
                        c.x = Math.max(0, Math.min(IMG_W - c.w, c.x + dx));
                        c.y = Math.max(0, Math.min(IMG_H - c.h, c.y + dy));
                        const el = document.getElementById(c.id);
                        if (el) applyCellPos(el, c);
                    });

                    refreshSelectedCoords();
                    refreshLog();
                    markDirty();
                }
            }
        })
        .resizable({
            edges: { left: '.rh-nw,.rh-w,.rh-sw', right: '.rh-ne,.rh-e,.rh-se',
                     top: '.rh-nw,.rh-n,.rh-ne',  bottom: '.rh-sw,.rh-s,.rh-se' },
            listeners: {
                move(ev) {
                    const newW = Math.max(10, Math.round(ev.rect.width  / state.zoom));
                    const newH = Math.max(10, Math.round(ev.rect.height / state.zoom));
                    const ddx = ev.edges.left ? Math.round(ev.deltaRect.left / state.zoom) : 0;
                    const ddy = ev.edges.top  ? Math.round(ev.deltaRect.top  / state.zoom) : 0;
                    const dw = newW - cell.w;
                    const dh = newH - cell.h;

                    // Se tem multi-seleção, propaga o delta de tamanho para todas
                    const targets = state.multiSelection.size > 0
                        ? state.cells.filter(c => state.multiSelection.has(c.id))
                        : [cell];

                    targets.forEach(c => {
                        if (ddx !== 0) c.x = Math.max(0, c.x + ddx);
                        if (ddy !== 0) c.y = Math.max(0, c.y + ddy);
                        c.w = Math.max(10, c.w + dw);
                        c.h = Math.max(10, c.h + dh);
                        const el = document.getElementById(c.id);
                        if (el) applyCellPos(el, c);
                    });

                    refreshSelectedCoords();
                    refreshLog();
                    markDirty();
                }
            },
            modifiers: [
                interact.modifiers.restrictSize({ min: { width: 10, height: 10 } })
            ]
        });

    canvasEl.appendChild(box);
}

function applyCellPos(el, cell) {
    el.style.left   = cell.x + 'px';
    el.style.top    = cell.y + 'px';
    el.style.width  = cell.w + 'px';
    el.style.height = cell.h + 'px';
}

// ─────────────────────────────────────────
// Seleção simples
// ─────────────────────────────────────────
function selectCell(cell) {
    document.querySelectorAll('.cell-box.selected').forEach(el => el.classList.remove('selected'));
    state.selected = cell;

    const multiEd  = document.getElementById('multi-editor');
    const selTitle = document.getElementById('sel-title');

    if (!cell) {
        noSel.style.display  = '';
        coordEd.style.display = 'none';
        multiEd.style.display = 'none';
        selTitle.textContent  = 'Seleção';
        refreshSidebar();
        return;
    }

    document.getElementById(cell.id)?.classList.add('selected');
    noSel.style.display   = 'none';
    coordEd.style.display = '';
    multiEd.style.display = 'none';
    selTitle.textContent  = 'Célula selecionada';
    refreshSelectedCoords();
}

function refreshSelectedCoords() {
    if (!state.selected) return;
    const c = state.selected;
    document.getElementById('ed-left').value   = c.x;
    document.getElementById('ed-top').value    = c.y;
    document.getElementById('ed-width').value  = c.w;
    document.getElementById('ed-height').value = c.h;
    document.getElementById('ed-name').value   = c.name;
    refreshSidebar();
}

['ed-left','ed-top','ed-width','ed-height','ed-name'].forEach(id => {
    document.getElementById(id).addEventListener('change', () => {
        if (!state.selected) return;
        const c = state.selected;
        c.x    = parseInt(document.getElementById('ed-left').value)   || c.x;
        c.y    = parseInt(document.getElementById('ed-top').value)    || c.y;
        c.w    = parseInt(document.getElementById('ed-width').value)  || c.w;
        c.h    = parseInt(document.getElementById('ed-height').value) || c.h;
        c.name = document.getElementById('ed-name').value.trim()      || c.name;
        const boxEl = document.getElementById(c.id);
        if (boxEl) {
            applyCellPos(boxEl, c);
            boxEl.querySelector('.cell-label').textContent = c.name;
        }
        refreshSidebar();
        refreshLog();
        markDirty();
    });
});

// ─────────────────────────────────────────
// Multi-seleção (Ctrl+Click)
// ─────────────────────────────────────────
function toggleMultiSelect(cell) {
    // Primeira: se havia seleção única, incluir ela na multi
    if (state.selected && state.multiSelection.size === 0) {
        state.multiSelection.add(state.selected.id);
    }

    if (state.multiSelection.has(cell.id)) {
        state.multiSelection.delete(cell.id);
        document.getElementById(cell.id)?.classList.remove('in-selection');
    } else {
        state.multiSelection.add(cell.id);
        document.getElementById(cell.id)?.classList.add('in-selection');
        // Remover seleção única ao entrar em modo multi
        document.querySelectorAll('.cell-box.selected').forEach(el => el.classList.remove('selected'));
        state.selected = null;
    }

    if (state.multiSelection.size === 0) {
        clearMultiSelection();
    } else if (state.multiSelection.size === 1) {
        // Voltar para seleção única
        const id = [...state.multiSelection][0];
        clearMultiSelection();
        selectCell(state.cells.find(c => c.id === id));
    } else {
        refreshMultiPanel();
    }

    refreshSidebar();
}

function clearMultiSelection() {
    document.querySelectorAll('.cell-box.in-selection').forEach(el => el.classList.remove('in-selection'));
    state.multiSelection.clear();
    document.getElementById('multi-editor').style.display = 'none';
    document.getElementById('sel-title').textContent = 'Seleção';
}

function refreshMultiPanel() {
    const count = state.multiSelection.size;
    noSel.style.display   = 'none';
    coordEd.style.display = 'none';
    document.getElementById('multi-editor').style.display = '';
    document.getElementById('multi-count').textContent = count;
    document.getElementById('sel-title').textContent = `Multi-seleção`;

    // Atualiza visual das células selecionadas
    state.cells.forEach(c => {
        const el = document.getElementById(c.id);
        if (!el) return;
        el.classList.toggle('in-selection', state.multiSelection.has(c.id));
        el.classList.remove('selected');
    });
}

// Aplicar tamanho fixo para todas
document.getElementById('btn-multi-apply-size').addEventListener('click', () => {
    const w = parseInt(document.getElementById('multi-width').value);
    const h = parseInt(document.getElementById('multi-height').value);
    state.cells.filter(c => state.multiSelection.has(c.id)).forEach(c => {
        if (!isNaN(w) && w > 0) c.w = w;
        if (!isNaN(h) && h > 0) c.h = h;
        const el = document.getElementById(c.id);
        if (el) applyCellPos(el, c);
    });
    refreshLog();
    markDirty();
    toast(`✓ Tamanho aplicado em ${state.multiSelection.size} células`);
});

// Aplicar delta (±) para todas
document.getElementById('btn-multi-apply-delta').addEventListener('click', () => {
    const dw = parseInt(document.getElementById('multi-dw').value) || 0;
    const dh = parseInt(document.getElementById('multi-dh').value) || 0;
    if (dw === 0 && dh === 0) return;
    state.cells.filter(c => state.multiSelection.has(c.id)).forEach(c => {
        c.w = Math.max(10, c.w + dw);
        c.h = Math.max(10, c.h + dh);
        const el = document.getElementById(c.id);
        if (el) applyCellPos(el, c);
    });
    refreshLog();
    markDirty();
    toast(`✓ Delta aplicado em ${state.multiSelection.size} células`);
});

// Deletar todas da multi-seleção
document.getElementById('btn-multi-delete').addEventListener('click', () => {
    if (!confirm(`Deletar ${state.multiSelection.size} células?`)) return;
    [...state.multiSelection].forEach(id => deleteCell(id));
    clearMultiSelection();
});

// ESC limpa multi-seleção
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        clearMultiSelection();
        selectCell(null);
    }
});

// ─────────────────────────────────────────
// Deletar
// ─────────────────────────────────────────
document.getElementById('btn-delete').addEventListener('click', () => {
    if (!state.selected) return;
    deleteCell(state.selected.id);
});

function deleteCell(id) {
    state.cells = state.cells.filter(c => c.id !== id);
    document.getElementById(id)?.remove();
    state.multiSelection.delete(id);
    if (state.selected?.id === id) selectCell(null);
    refreshSidebar();
    refreshLog();
    markDirty();
}

document.getElementById('btn-clear-all').addEventListener('click', () => {
    if (!confirm('Apagar todas as células?')) return;
    state.cells.forEach(c => document.getElementById(c.id)?.remove());
    state.cells = [];
    clearMultiSelection();
    selectCell(null);
    refreshSidebar();
    refreshLog();
    localStorage.removeItem(STORAGE_KEY);
    saveIndicator.textContent = '';
    isDirty = false;
});

// ─────────────────────────────────────────
// Sidebar — lista de células
// ─────────────────────────────────────────
function refreshSidebar() {
    cellCount.textContent = state.cells.length;
    cellsList.innerHTML = '';
    state.cells.forEach(cell => {
        const item = document.createElement('div');
        const isActive = state.selected?.id === cell.id || state.multiSelection.has(cell.id);
        item.className = 'cell-list-item' + (isActive ? ' active' : '');
        item.dataset.cellId = cell.id;

        item.innerHTML = `
            <div class="cell-list-item-info">
                <div class="cell-list-name">${esc(cell.name)}</div>
                <div class="cell-list-coords">${cell.x},${cell.y}  ${cell.w}×${cell.h}</div>
            </div>
            <button class="cell-list-del" title="Deletar">✕</button>
        `;

        item.addEventListener('click', (e) => {
            if (e.target.classList.contains('cell-list-del')) {
                deleteCell(cell.id);
            } else {
                selectCell(cell);
                // Scroll para a célula no canvas
                const el = document.getElementById(cell.id);
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });

        cellsList.appendChild(item);
    });
}

// ─────────────────────────────────────────
// Log de posições
// ─────────────────────────────────────────
function refreshLog() {
    if (state.cells.length === 0) {
        logPos.textContent  = 'Nenhuma célula ainda...';
        logCode.textContent = 'Nenhuma célula ainda...';
        return;
    }

    // Coluna esquerda: lista de coordenadas
    logPos.textContent = state.cells.map((c, i) =>
        `[${String(i+1).padStart(2,'0')}] ${c.name}\n` +
        `     left:${c.x}  top:${c.y}  w:${c.w}  h:${c.h}`
    ).join('\n');

    // Coluna direita: código HTML
    logCode.textContent = state.cells.map(c =>
        `<textarea class="field" name="${esc(c.name)}" style="left:${c.x}px;top:${c.y}px;` +
        `width:${c.w}px;height:${c.h}px;" placeholder="${esc(c.name)}"></textarea>`
    ).join('\n');
}

// ─────────────────────────────────────────
// Copiar código
// ─────────────────────────────────────────
document.getElementById('btn-copy').addEventListener('click', () => {
    if (state.cells.length === 0) { toast('Nenhuma célula para copiar'); return; }

    const code = state.cells.map(c =>
        `        <textarea class="field" name="${esc(c.name)}" style="left:${c.x}px;top:${c.y}px;width:${c.w}px;height:${c.h}px;" placeholder="${esc(c.name)}"></textarea>`
    ).join('\n');

    navigator.clipboard.writeText(code)
        .then(() => toast('✓ Código copiado!'))
        .catch(() => {
            // fallback
            const ta = document.createElement('textarea');
            ta.value = code;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            toast('✓ Código copiado!');
        });
});

// ─────────────────────────────────────────
// Log toggle
// ─────────────────────────────────────────
let logOpen = true;
document.getElementById('log-header').addEventListener('click', () => {
    logOpen = !logOpen;
    document.getElementById('log-body').style.display = logOpen ? '' : 'none';
    document.getElementById('log-panel').style.height = logOpen ? '180px' : '30px';
    document.getElementById('log-toggle-btn').textContent = logOpen ? '▼ recolher' : '▲ expandir';
});

// ─────────────────────────────────────────
// Toast
// ─────────────────────────────────────────
function toast(msg) {
    const t = document.createElement('div');
    t.className = 'toast';
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 2200);
}

// ─────────────────────────────────────────
// Utilitários
// ─────────────────────────────────────────
function esc(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

// ─────────────────────────────────────────
// Modal de Documentos Salvos
// ─────────────────────────────────────────
const docsBackdrop = document.getElementById('docs-modal-backdrop');
const docsList     = document.getElementById('docs-list');
const docsEmpty    = document.getElementById('docs-empty');
const STORAGE_PREFIX = 'cell-editor:';

function getAllSavedDocs() {
    const docs = [];
    for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i);
        if (!key.startsWith(STORAGE_PREFIX)) continue;
        try {
            const data = JSON.parse(localStorage.getItem(key));
            if (!data) continue;
            docs.push({
                key,
                formulario: key.slice(STORAGE_PREFIX.length),
                cells: data.cells?.length ?? 0,
                savedAt: data.savedAt ?? null,
            });
        } catch (e) { /* ignora entradas corrompidas */ }
    }
    // Ordenar: atual primeiro, depois por data decrescente
    docs.sort((a, b) => {
        if (a.formulario === CURRENT_FORM) return -1;
        if (b.formulario === CURRENT_FORM) return 1;
        return new Date(b.savedAt) - new Date(a.savedAt);
    });
    return docs;
}

function renderDocsList() {
    const docs = getAllSavedDocs();
    docsList.innerHTML = '';

    if (docs.length === 0) {
        docsList.appendChild(docsEmpty);
        return;
    }

    docs.forEach(doc => {
        const isCurrent = doc.formulario === CURRENT_FORM;
        const savedTime = doc.savedAt
            ? new Date(doc.savedAt).toLocaleString('pt-BR')
            : 'Data desconhecida';

        const item = document.createElement('div');
        item.className = 'doc-item' + (isCurrent ? ' current' : '');

        item.innerHTML = `
            <div class="doc-icon">${isCurrent ? '📝' : '📄'}</div>
            <div class="doc-info">
                <div class="doc-formulario">Formulário ${esc(doc.formulario)}${isCurrent ? ' <span style="font-size:10px;color:#69f0ae;">(atual)</span>' : ''}</div>
                <div class="doc-meta">Salvo em ${savedTime}</div>
            </div>
            <div class="doc-cells-badge">${doc.cells} ${doc.cells === 1 ? 'célula' : 'células'}</div>
            <button class="doc-del-btn" data-key="${esc(doc.key)}" title="Apagar este save">🗑️</button>
        `;

        // Clicar no item abre o editor (exceto no botão de deletar)
        item.addEventListener('click', (e) => {
            if (e.target.classList.contains('doc-del-btn')) return;
            if (isCurrent) {
                docsBackdrop.classList.remove('open');
            } else {
                window.location.href = `${CELL_EDITOR_BASE_URL}?formulario=${encodeURIComponent(doc.formulario)}${CELL_EDITOR_QUERY_SUFFIX}`;
            }
        });

        // Botão deletar
        item.querySelector('.doc-del-btn').addEventListener('click', (e) => {
            e.stopPropagation();
            if (!confirm(`Apagar o save do formulário ${doc.formulario}?`)) return;
            localStorage.removeItem(doc.key);
            if (isCurrent) {
                saveIndicator.textContent = '';
                isDirty = false;
            }
            renderDocsList();
        });

        docsList.appendChild(item);
    });
}

document.getElementById('btn-docs').addEventListener('click', () => {
    renderDocsList();
    docsBackdrop.classList.add('open');
});

document.getElementById('btn-docs-close').addEventListener('click', () => {
    docsBackdrop.classList.remove('open');
});

docsBackdrop.addEventListener('click', (e) => {
    if (e.target === docsBackdrop) docsBackdrop.classList.remove('open');
});

// ─────────────────────────────────────────
// Modo: Célula vs Grade
// ─────────────────────────────────────────
let mode = 'cell'; // 'cell' | 'grid'
let pendingGridArea = null; // área desenhada aguardando modal

document.getElementById('btn-mode-cell').addEventListener('click', () => setMode('cell'));
document.getElementById('btn-mode-grid').addEventListener('click', () => setMode('grid'));

function setMode(m) {
    mode = m;
    document.getElementById('btn-mode-cell').classList.toggle('active', m === 'cell');
    document.getElementById('btn-mode-grid').classList.toggle('active', m === 'grid');
    document.getElementById('cell-mode-controls').style.display = m === 'cell' ? '' : 'none';
    document.getElementById('grid-mode-controls').style.display = m === 'grid' ? '' : 'none';
}

// ─────────────────────────────────────────
// Modal de Grade
// ─────────────────────────────────────────
const gridBackdrop = document.getElementById('grid-modal-backdrop');
const gridRowsEl   = document.getElementById('grid-rows');
const gridColsEl   = document.getElementById('grid-cols');
const gridTable    = document.getElementById('grid-name-table');
const gridPreview  = document.getElementById('grid-preview');
const gridEnableColPrefixEl = document.getElementById('grid-enable-col-prefix');
const gridEnableRowPrefixEl = document.getElementById('grid-enable-row-prefix');
const gridEnableRowNumberEl = document.getElementById('grid-enable-row-number');
const gridEnableColNumberEl = document.getElementById('grid-enable-col-number');

// Gera a tabela de inputs — coluna de prefixo por linha sempre visível
function buildGridNameTable() {
    const rows = Math.max(1, Math.min(100, parseInt(gridRowsEl.value) || 1));
    const cols = Math.max(1, Math.min(50,  parseInt(gridColsEl.value) || 1));

    // Preservar valores já digitados
    const oldColPfx = {};
    const oldRowPfx = {};
    const oldValues = {};
    gridTable.querySelectorAll('input[data-prefix]').forEach(inp => {
        oldColPfx[inp.dataset.prefix] = inp.value;
    });
    gridTable.querySelectorAll('input[data-row-prefix]').forEach(inp => {
        oldRowPfx[inp.dataset.rowPrefix] = inp.value;
    });
    gridTable.querySelectorAll('input[data-r]').forEach(inp => {
        oldValues[`${inp.dataset.r}_${inp.dataset.c}`] = {
            val: inp.value, auto: inp.classList.contains('auto-filled'),
        };
    });

    // ── Cabeçalho: [vazio] | Pfx Linha | Col 1 | Col 2 … ──
    let html = '<thead><tr><th></th><th class="row-prefix-header">Pfx Linha</th>';
    for (let c = 0; c < cols; c++) html += `<th>Col ${c + 1}</th>`;
    html += '</tr></thead><tbody>';

    // ── Linha de prefixos de coluna: [Pfx Col] | (vazio) | inp… ──
    html += '<tr class="prefix-row"><td class="prefix-row-label">Pfx Col</td>';
    html += '<td class="row-prefix-cell prefix-row-empty"></td>';
    for (let c = 0; c < cols; c++) {
        const v = esc(oldColPfx[c] ?? '');
        html += `<td><input type="text" data-prefix="${c}" placeholder="pfx_col${c+1}" value="${v}"></td>`;
    }
    html += '</tr>';

    // ── Linhas de dados ──
    for (let r = 0; r < rows; r++) {
        const vr = esc(oldRowPfx[r] ?? '');
        html += `<tr>
            <th style="background:#1e3a4a;color:#4fc3f7;font-size:10px;padding:4px 6px;border:1px solid #3c3c3c;text-align:center;white-space:nowrap;">Lin ${r+1}</th>
            <td class="row-prefix-cell"><input type="text" data-row-prefix="${r}" placeholder="pfx_lin${r+1}" value="${vr}"></td>`;
        for (let c = 0; c < cols; c++) {
            const old  = oldValues[`${r}_${c}`];
            const val  = old ? esc(old.val) : '';
            const auto = old?.auto ? 'auto-filled' : '';
            html += `<td><input type="text" data-r="${r}" data-c="${c}" value="${val}" class="${auto}" placeholder="cel_${r+1}_${c+1}"></td>`;
        }
        html += '</tr>';
    }
    html += '</tbody>';
    gridTable.innerHTML = html;

    // ── Eventos: prefixo de coluna ──
    gridTable.querySelectorAll('input[data-prefix]').forEach(inp => {
        const col = parseInt(inp.dataset.prefix);
        inp.addEventListener('input', () => {
            // Força recompute em todas as células auto-preenchidas da coluna
            gridTable.querySelectorAll(`input[data-c="${col}"]`).forEach(cell => {
                if (cell.dataset.r !== undefined) recomputeCell(parseInt(cell.dataset.r), col, true);
            });
            updateGridPreview();
        });
    });

    // ── Eventos: prefixo de linha ──
    gridTable.querySelectorAll('input[data-row-prefix]').forEach(inp => {
        const row = parseInt(inp.dataset.rowPrefix);
        inp.addEventListener('input', () => {
            // Força recompute em todas as células auto-preenchidas da linha
            gridTable.querySelectorAll(`input[data-r="${row}"]`).forEach(cell => {
                if (cell.dataset.c !== undefined) recomputeCell(row, parseInt(cell.dataset.c), true);
            });
            updateGridPreview();
        });
    });

    // ── Eventos: célula manual → remove auto ──
    gridTable.querySelectorAll('input[data-r]').forEach(inp => {
        inp.addEventListener('input', () => {
            inp.classList.remove('auto-filled');
            updateGridPreview();
        });
        inp.addEventListener('keydown', (e) => {
            if (e.key !== 'Enter') return;
            e.preventDefault();
            const all = [...gridTable.querySelectorAll('input')];
            all[all.indexOf(inp) + 1]?.focus();
        });
    });

    // ── Re-aplicar prefixos preservados ──
    for (let r = 0; r < rows; r++)
        for (let c = 0; c < cols; c++)
            recomputeCell(r, c, false);

    updateGridPreview();
}

// Calcula e aplica o nome combinado para a célula (r, c)
// Regra: colPrefix_rowPrefix  |  só col: colPrefix_N  |  só row: rowPrefix_N
function recomputeCell(r, c, force = false) {
    const inp = gridTable.querySelector(`input[data-r="${r}"][data-c="${c}"]`);
    if (!inp) return;
    // Não sobreescrever edição manual, a menos que force=true
    if (!force && inp.value && !inp.classList.contains('auto-filled')) return;

    const useColPrefix = gridEnableColPrefixEl.checked;
    const useRowPrefix = gridEnableRowPrefixEl.checked;
    const useRowNumber = gridEnableRowNumberEl.checked;
    const useColNumber = gridEnableColNumberEl.checked;
    const cp = useColPrefix ? (gridTable.querySelector(`input[data-prefix="${c}"]`)?.value.trim() || '') : '';
    const rp = useRowPrefix ? (gridTable.querySelector(`input[data-row-prefix="${r}"]`)?.value.trim() || '') : '';

    const parts = [];
    if (cp) parts.push(cp);
    if (rp) parts.push(rp);
    if (useRowNumber) parts.push(String(r + 1));
    if (useColNumber) parts.push(String(c + 1));

    if (parts.length > 0) {
        inp.value = parts.join('_');
        inp.classList.add('auto-filled');
    } else if (inp.classList.contains('auto-filled')) {
        inp.value = '';
        inp.classList.remove('auto-filled');
    }
}

// Aliases mantidos para compatibilidade
function applyColPrefix(col, prefix, onlyIfAutoOrEmpty = false) {
    const rows = parseInt(gridRowsEl.value) || 1;
    for (let r = 0; r < rows; r++) {
        const inp = gridTable.querySelector(`input[data-r="${r}"][data-c="${col}"]`);
        if (!inp) continue;
        if (onlyIfAutoOrEmpty && inp.value && !inp.classList.contains('auto-filled')) continue;
        recomputeCell(r, col, true);
    }
    updateGridPreview();
}

function applyRowPrefix(row, prefix, onlyIfAutoOrEmpty = false) {
    const cols = parseInt(gridColsEl.value) || 1;
    for (let c = 0; c < cols; c++) {
        const inp = gridTable.querySelector(`input[data-r="${row}"][data-c="${c}"]`);
        if (!inp) continue;
        if (onlyIfAutoOrEmpty && inp.value && !inp.classList.contains('auto-filled')) continue;
        recomputeCell(row, c, true);
    }
    updateGridPreview();
}

function applyPrefix(col, prefix, onlyIfAutoOrEmpty = false) {
    applyColPrefix(col, prefix, onlyIfAutoOrEmpty);
}

function recomputeAllGridCells(force = true) {
    const rows = parseInt(gridRowsEl.value) || 1;
    const cols = parseInt(gridColsEl.value) || 1;
    for (let r = 0; r < rows; r++) {
        for (let c = 0; c < cols; c++) {
            recomputeCell(r, c, force);
        }
    }
    updateGridPreview();
}

function updateGridPreview() {
    const rows = parseInt(gridRowsEl.value) || 1;
    const cols = parseInt(gridColsEl.value) || 1;

    gridPreview.style.gridTemplateColumns = `repeat(${cols}, 1fr)`;
    gridPreview.innerHTML = '';

    for (let r = 0; r < rows; r++) {
        for (let c = 0; c < cols; c++) {
            const inp = gridTable.querySelector(`input[data-r="${r}"][data-c="${c}"]`);
            const name = inp ? (inp.value.trim() || inp.placeholder) : `cel_${r+1}_${c+1}`;
            const cell = document.createElement('div');
            cell.className = 'gp-cell';
            if (inp?.classList.contains('auto-filled')) cell.style.color = '#69f0ae';
            cell.textContent = name;
            gridPreview.appendChild(cell);
        }
    }
}

document.getElementById('btn-grid-apply').addEventListener('click', buildGridNameTable);

[gridRowsEl, gridColsEl].forEach(el => {
    el.addEventListener('change', buildGridNameTable);
});

[gridEnableColPrefixEl, gridEnableRowPrefixEl, gridEnableRowNumberEl, gridEnableColNumberEl].forEach(el => {
    el.addEventListener('change', () => {
        recomputeAllGridCells(true);
    });
});

// Cancelar
document.getElementById('btn-grid-cancel').addEventListener('click', () => {
    gridBackdrop.classList.remove('open');
    pendingGridArea = null;
});

gridBackdrop.addEventListener('click', (e) => {
    if (e.target === gridBackdrop) {
        gridBackdrop.classList.remove('open');
        pendingGridArea = null;
    }
});

// Confirmar — criar todas as células da grade
document.getElementById('btn-grid-confirm').addEventListener('click', () => {
    if (!pendingGridArea) return;

    const rows = parseInt(gridRowsEl.value) || 1;
    const cols = parseInt(gridColsEl.value) || 1;
    const { x, y, w, h } = pendingGridArea;

    const cellW = Math.round(w / cols);
    const cellH = Math.round(h / rows);

    for (let r = 0; r < rows; r++) {
        for (let c = 0; c < cols; c++) {
            const inp = gridTable.querySelector(`input[data-r="${r}"][data-c="${c}"]`);
            const name = inp ? (inp.value.trim() || inp.placeholder) : `cel_${r+1}_${c+1}`;

            const cx = x + c * cellW;
            const cy = y + r * cellH;
            // Última coluna/linha pega o resto para compensar arredondamento
            const cw = (c === cols - 1) ? (w - c * cellW) : cellW;
            const ch = (r === rows - 1) ? (h - r * cellH) : cellH;

            addCell(name, cx, cy, cw, ch);
        }
    }

    gridBackdrop.classList.remove('open');
    pendingGridArea = null;
    setMode('cell'); // Voltar para modo célula
    toast(`✓ ${rows * cols} células criadas`);
});

// Abrir modal e construir tabela inicial
function openGridModal(area) {
    pendingGridArea = area;
    gridBackdrop.classList.add('open');
    buildGridNameTable();
    gridRowsEl.focus();
}

// ─────────────────────────────────────────
// Persistência — localStorage
// ─────────────────────────────────────────
const STORAGE_KEY = `cell-editor:${CURRENT_FORM}`;
const saveIndicator = document.getElementById('save-indicator');
let autoSaveTimer = null;
let isDirty = false;

function saveToStorage() {
    const data = {
        savedAt: new Date().toISOString(),
        cells: state.cells.map(c => ({ id: c.id, name: c.name, x: c.x, y: c.y, w: c.w, h: c.h }))
    };
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
        isDirty = false;
        markSaved(data.savedAt);
    } catch (e) {
        toast('Erro ao salvar: ' + e.message);
    }
}

function markSaved(isoDate) {
    const btn = document.getElementById('btn-save');
    btn.textContent = '✓ Salvo';
    btn.classList.add('saved');
    const time = new Date(isoDate).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    saveIndicator.textContent = 'Salvo às ' + time;
    setTimeout(() => {
        btn.textContent = '💾 Salvar';
        btn.classList.remove('saved');
    }, 2000);
}

function markDirty() {
    isDirty = true;
    saveIndicator.textContent = '● não salvo';
    saveIndicator.style.color = '#ff9800';

    // Auto-save sempre reinicia a cada nova mudança
    clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(() => {
        saveToStorage();
        saveIndicator.style.color = '#666';
    }, 3000);
}

function loadFromStorage() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) return false;
        const data = JSON.parse(raw);
        if (!data?.cells?.length) return false;
        return data;
    } catch (e) {
        return false;
    }
}

function restoreCells(cells) {
    cells.forEach(c => {
        // Garantir que IDs não colidam se já houve addCell antes
        const cell = { id: c.id || ('c' + Date.now() + Math.random()), name: c.name, x: c.x, y: c.y, w: c.w, h: c.h };
        state.cells.push(cell);
        mountCell(cell);
    });
    refreshSidebar();
    refreshLog();
}

document.getElementById('btn-save').addEventListener('click', () => {
    saveToStorage();
    toast('💾 Salvo!');
});

// Ctrl+S para salvar
document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        saveToStorage();
        toast('💾 Salvo!');
    }
});

// ─────────────────────────────────────────
// Detectar Células Automaticamente (análise de pixels)
// ─────────────────────────────────────────
const detectBackdrop    = document.getElementById('detect-modal-backdrop');
const detectResultsList = document.getElementById('detect-results-list');
const detectStatusText  = document.getElementById('detect-status-text');
const detectProgressBar = document.getElementById('detect-progress-bar');
const detectSummary     = document.getElementById('detect-summary');
const detectEmptyMsg    = document.getElementById('detect-empty-msg');

// ── Crop de região da imagem → dataURL ──
function cropImageRegion(srcImg, x, y, w, h) {
    const c = document.createElement('canvas');
    c.width  = Math.max(1, w);
    c.height = Math.max(1, h);
    c.getContext('2d').drawImage(srcImg, x, y, w, h, 0, 0, w, h);
    return c.toDataURL('image/png');
}

// ── Análise de pixels: detecta retângulos claros com borda escura ──
function analyzeImageForFields() {
    const SCALE = 0.18;                          // trabalha em 18% — ~298 × 421 px
    const anaW  = Math.round(IMG_W * SCALE);
    const anaH  = Math.round(IMG_H * SCALE);

    const anaCanvas = document.createElement('canvas');
    anaCanvas.width  = anaW;
    anaCanvas.height = anaH;
    const ctx = anaCanvas.getContext('2d');
    ctx.drawImage(imgEl, 0, 0, anaW, anaH);
    const px = ctx.getImageData(0, 0, anaW, anaH).data;

    // Grayscale + máscara "claro" (> 225) e "escuro" (< 70)
    const gray  = new Float32Array(anaW * anaH);
    const light = new Uint8Array(anaW * anaH);
    const dark  = new Uint8Array(anaW * anaH);
    for (let i = 0; i < anaW * anaH; i++) {
        const g = 0.299 * px[i*4] + 0.587 * px[i*4+1] + 0.114 * px[i*4+2];
        gray[i]  = g;
        light[i] = g > 225 ? 1 : 0;
        dark[i]  = g < 70  ? 1 : 0;
    }

    // ── 1. Fração de pixels claros por linha (smoothed) ──
    const rowLight = new Float32Array(anaH);
    for (let y = 0; y < anaH; y++) {
        let cnt = 0;
        for (let x = 0; x < anaW; x++) cnt += light[y * anaW + x];
        rowLight[y] = cnt / anaW;
    }
    // Suaviza (janela 3)
    const rowLightS = new Float32Array(anaH);
    for (let y = 0; y < anaH; y++) {
        rowLightS[y] = (rowLight[Math.max(0,y-1)] + rowLight[y] + rowLight[Math.min(anaH-1,y+1)]) / 3;
    }

    // ── 2. Grupos de linhas claras (zonas candidatas) ──
    const ROW_THRESH = 0.72;     // 72% dos pixels da linha devem ser claros
    const MIN_ZONE_H = Math.max(2, Math.round(0.007 * anaH));  // ≥ ~1.6px scaled = ≥ 9px original
    const MERGE_GAP  = Math.max(1, Math.round(0.005 * anaH));  // fecha buracos ≤ 2px no scaled

    const zones = [];
    let zs = -1;
    for (let y = 0; y <= anaH; y++) {
        const ok = y < anaH && rowLightS[y] > ROW_THRESH;
        if (ok && zs < 0) { zs = y; }
        else if (!ok && zs >= 0) {
            if (y - zs >= MIN_ZONE_H) zones.push({ y1: zs, y2: y - 1 });
            zs = -1;
        }
    }
    // Funde zonas com gap pequeno
    const mergedZones = [];
    for (const z of zones) {
        if (mergedZones.length && z.y1 - mergedZones[mergedZones.length-1].y2 <= MERGE_GAP) {
            mergedZones[mergedZones.length-1].y2 = z.y2;
        } else {
            mergedZones.push({ ...z });
        }
    }

    // ── 3. Para cada zona, fração de pixels claros por coluna ──
    const COL_THRESH   = 0.72;
    const MIN_FIELD_W  = Math.max(6, Math.round(0.025 * anaW));  // ≥ ~7px scaled = ≥ 40px original
    const MIN_FIELD_H  = Math.max(2, Math.round(0.006 * anaH));  // ≥ ~2px scaled = ≥ 12px original
    const MAX_FIELD_W  = anaW * 0.97;
    const MAX_FIELD_H  = anaH * 0.18;
    const COL_MERGE    = Math.max(1, Math.round(0.004 * anaW));

    const candidates = [];

    for (const zone of mergedZones) {
        const zh = zone.y2 - zone.y1 + 1;
        if (zh < MIN_FIELD_H) continue;

        const colLight = new Float32Array(anaW);
        for (let x = 0; x < anaW; x++) {
            let cnt = 0;
            for (let y = zone.y1; y <= zone.y2; y++) cnt += light[y * anaW + x];
            colLight[x] = cnt / zh;
        }

        // Grupos de colunas claras
        const colGroups = [];
        let cs = -1;
        for (let x = 0; x <= anaW; x++) {
            const ok = x < anaW && colLight[x] > COL_THRESH;
            if (ok && cs < 0) { cs = x; }
            else if (!ok && cs >= 0) {
                colGroups.push({ x1: cs, x2: x - 1 });
                cs = -1;
            }
        }
        // Funde grupos próximos
        const mcg = [];
        for (const g of colGroups) {
            if (mcg.length && g.x1 - mcg[mcg.length-1].x2 <= COL_MERGE) {
                mcg[mcg.length-1].x2 = g.x2;
            } else {
                mcg.push({ ...g });
            }
        }

        for (const cg of mcg) {
            const cw = cg.x2 - cg.x1 + 1;
            if (cw < MIN_FIELD_W || cw > MAX_FIELD_W || zh > MAX_FIELD_H) continue;

            // Validar: checar bordas escuras em pelo menos 1 lado
            if (!hasBorder(dark, anaW, anaH, cg.x1, zone.y1, cg.x2, zone.y2)) continue;

            // Escalar de volta para coordenadas originais
            candidates.push({
                x: Math.round(cg.x1  / SCALE),
                y: Math.round(zone.y1 / SCALE),
                w: Math.round(cw      / SCALE),
                h: Math.round(zh      / SCALE),
            });
        }
    }

    // ── 4. NMS — remove sobreposições ──
    return nmsBoxes(candidates, 0.55);
}

// Verifica se o retângulo tem borda escura em pelo menos 1 lado
function hasBorder(dark, anaW, anaH, x1, y1, x2, y2) {
    const DARK_THRESH = 0.20; // 20% dos pixels da borda devem ser escuros
    const CHECK = 2;

    function edgeRatio(xs, xe, ys, ye) {
        let cnt = 0, tot = 0;
        for (let y = Math.max(0,ys); y <= Math.min(anaH-1,ye); y++)
            for (let x = Math.max(0,xs); x <= Math.min(anaW-1,xe); x++) {
                tot++; if (dark[y * anaW + x]) cnt++;
            }
        return tot > 0 ? cnt / tot : 0;
    }

    const top    = edgeRatio(x1, x2, y1 - CHECK, y1 - 1);
    const bottom = edgeRatio(x1, x2, y2 + 1,     y2 + CHECK);
    const left   = edgeRatio(x1 - CHECK, x1 - 1, y1, y2);
    const right  = edgeRatio(x2 + 1, x2 + CHECK, y1, y2);

    return [top, bottom, left, right].some(r => r >= DARK_THRESH);
}

// NMS simples por IoU
function nmsBoxes(boxes, iouThr) {
    const sorted = [...boxes].sort((a, b) => (b.w * b.h) - (a.w * a.h));
    const keep = [], sup = new Set();
    for (let i = 0; i < sorted.length; i++) {
        if (sup.has(i)) continue;
        keep.push(sorted[i]);
        for (let j = i + 1; j < sorted.length; j++) {
            if (sup.has(j)) continue;
            const a = sorted[i], b = sorted[j];
            const ix1 = Math.max(a.x, b.x), iy1 = Math.max(a.y, b.y);
            const ix2 = Math.min(a.x+a.w, b.x+b.w), iy2 = Math.min(a.y+a.h, b.y+b.h);
            if (ix2 <= ix1 || iy2 <= iy1) continue;
            const inter = (ix2-ix1) * (iy2-iy1);
            const un    = a.w*a.h + b.w*b.h - inter;
            if (inter / un > iouThr) sup.add(j);
        }
    }
    return keep.sort((a, b) => a.y !== b.y ? a.y - b.y : a.x - b.x);
}

// ── Carrega Tesseract.js sob demanda ──
let tesseractLoaded = false;
function loadTesseract() {
    return new Promise((resolve, reject) => {
        if (tesseractLoaded) { resolve(); return; }
        const s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js';
        s.onload  = () => { tesseractLoaded = true; resolve(); };
        s.onerror = () => reject(new Error('Falha ao carregar Tesseract.js'));
        document.head.appendChild(s);
    });
}

function ocrToFieldName(raw) {
    return raw.replace(/[\r\n]+/g,' ').replace(/\s{2,}/g,' ')
              .replace(/[^a-zA-ZÀ-ÿ0-9\s_\-]/g,'')
              .trim().toLowerCase().replace(/\s+/g,'_')
              .replace(/_+/g,'_').replace(/^_|_$/g,'').slice(0,60);
}

// ── Monta item de resultado no modal ──
function buildDetectItem(box, idx, thumbUrl) {
    const item = document.createElement('div');
    item.className = 'detect-item accepted'; // aceito por padrão
    item.dataset.boxX = box.x; item.dataset.boxY = box.y;
    item.dataset.boxW = box.w; item.dataset.boxH = box.h;

    const defaultName = `campo_${idx + 1}`;

    item.innerHTML = `
        <div class="detect-ocr-preview">
            <img src="${thumbUrl}" width="80" height="50"
                 style="display:block;object-fit:cover;image-rendering:pixelated;" alt="">
        </div>
        <div class="detect-item-names">
            <div class="detect-item-current" style="margin-bottom:4px;">
                <span style="font-size:10px;color:#888;">Nome:</span>
            </div>
            <div class="detect-item-suggestion">
                <input type="text" class="detect-suggestion-input"
                       value="${esc(defaultName)}" placeholder="${esc(defaultName)}">
            </div>
        </div>
        <div class="detect-item-coords">
            ${box.x},${box.y}<br>${box.w}×${box.h}
        </div>
        <button class="detect-btn-accept" title="Incluir esta célula">✓</button>
        <button class="detect-btn-skip"   title="Ignorar esta célula">✕</button>
    `;

    const acceptBtn = item.querySelector('.detect-btn-accept');
    const skipBtn   = item.querySelector('.detect-btn-skip');

    acceptBtn.addEventListener('click', () => {
        const on = item.classList.toggle('accepted');
        if (on) item.classList.remove('skipped');
        updateDetectSummary();
    });
    skipBtn.addEventListener('click', () => {
        item.classList.remove('accepted');
        item.classList.add('skipped');
        updateDetectSummary();
    });

    return item;
}

function updateDetectSummary() {
    const total    = detectResultsList.querySelectorAll('.detect-item').length;
    const accepted = detectResultsList.querySelectorAll('.detect-item.accepted').length;
    detectSummary.textContent = `${total} campos detectados · ${accepted} selecionados`;
    document.getElementById('btn-detect-apply-all').style.display = total > 0 ? '' : 'none';
}

let detectRunning = false;

async function runDetection() {
    if (detectRunning) return;
    detectRunning = true;

    const btnRun = document.getElementById('btn-detect-run');
    btnRun.disabled = true;
    btnRun.textContent = '⏳ Analisando…';
    document.getElementById('btn-detect-apply-all').style.display = 'none';
    detectResultsList.innerHTML = '';
    detectProgressBar.style.width = '5%';
    detectStatusText.textContent = 'Analisando pixels do formulário…';

    try {
        // ─ Fase 1: detectar retângulos claros na imagem ─
        await new Promise(r => setTimeout(r, 30)); // deixa o browser atualizar a UI
        const boxes = analyzeImageForFields();

        detectProgressBar.style.width = '40%';
        detectStatusText.textContent  = `${boxes.length} campo(s) encontrado(s). Gerando pré-visualizações…`;

        if (boxes.length === 0) {
            detectResultsList.innerHTML = '<div class="detect-empty">Nenhum campo detectado. Tente ajustar manualmente.</div>';
            detectProgressBar.style.width = '100%';
            detectStatusText.textContent = '⚠ Nenhum campo detectado na imagem.';
            updateDetectSummary();
            return;
        }

        // Limitar a 80 campos para performance
        const limited = boxes.slice(0, 80);
        const withOcr = document.getElementById('detect-with-ocr').checked;

        // ─ Fase 2 (opcional): OCR para nomear ─
        let worker = null;
        if (withOcr) {
            detectStatusText.textContent = 'Carregando Tesseract.js…';
            await loadTesseract();
            detectStatusText.textContent = 'Inicializando OCR…';
            worker = await Tesseract.createWorker('por+eng', 1, { logger: () => {} });
        }

        for (let i = 0; i < limited.length; i++) {
            const box = limited[i];
            const pct = 40 + Math.round((i / limited.length) * 55);
            detectProgressBar.style.width = pct + '%';
            detectStatusText.textContent  = withOcr
                ? `OCR campo ${i+1}/${limited.length}…`
                : `Preparando campo ${i+1}/${limited.length}…`;

            const thumbUrl = cropImageRegion(imgEl, box.x, box.y, box.w, box.h);
            const item = buildDetectItem(box, i, thumbUrl);

            // OCR na região ACIMA do campo para sugerir nome
            if (withOcr && worker) {
                const labelH = Math.min(60, box.y);
                const labelY = box.y - labelH;
                if (labelH >= 10 && box.w >= 10) {
                    try {
                        const labelUrl = cropImageRegion(imgEl, box.x, labelY, box.w, labelH);
                        const result   = await worker.recognize(labelUrl);
                        const name     = ocrToFieldName(result.data.text || '');
                        if (name && name.length >= 2) {
                            item.querySelector('.detect-suggestion-input').value = name;
                        }
                    } catch (_) { /* ignora erros OCR */ }
                }
            }

            detectResultsList.appendChild(item);
            updateDetectSummary();
        }

        if (worker) await worker.terminate();

        detectProgressBar.style.width = '100%';
        detectStatusText.textContent  = `✓ ${limited.length} campo(s) detectado(s) — revise e clique em "Criar Células Aceitas"`;

    } catch (err) {
        detectStatusText.textContent = '⚠ Erro: ' + err.message;
        console.error('[detectar]', err);
    } finally {
        detectRunning = false;
        btnRun.disabled = false;
        btnRun.textContent = '▶ Detectar Campos';
        updateDetectSummary();
    }
}

// ── Criar células aceitas ──
document.getElementById('btn-detect-apply-all').addEventListener('click', () => {
    const accepted = detectResultsList.querySelectorAll('.detect-item.accepted');
    let count = 0;
    accepted.forEach(item => {
        const x    = parseInt(item.dataset.boxX);
        const y    = parseInt(item.dataset.boxY);
        const w    = parseInt(item.dataset.boxW);
        const h    = parseInt(item.dataset.boxH);
        const name = item.querySelector('.detect-suggestion-input').value.trim()
                     || `campo_${count + 1}`;
        addCell(name, x, y, w, h);
        count++;
    });
    if (count > 0) {
        detectBackdrop.classList.remove('open');
        toast(`✓ ${count} célula(s) criada(s)!`);
    }
});

document.getElementById('btn-detect').addEventListener('click', () => {
    detectBackdrop.classList.add('open');
});

document.getElementById('btn-detect-close').addEventListener('click', () => {
    detectBackdrop.classList.remove('open');
});

detectBackdrop.addEventListener('click', (e) => {
    if (e.target === detectBackdrop) detectBackdrop.classList.remove('open');
});

document.getElementById('btn-detect-run').addEventListener('click', () => {
    detectEmptyMsg.style.display = 'none';
    runDetection();
});

// ─────────────────────────────────────────
// Init — esperar imagem carregar
// ─────────────────────────────────────────
function init() {
    canvasEl.style.width  = IMG_W + 'px';
    canvasEl.style.height = IMG_H + 'px';
    imgEl.style.width     = IMG_W + 'px';
    imgEl.style.height    = IMG_H + 'px';
    fitZoom();

    // Restaurar células salvas
    const saved = loadFromStorage();
    if (saved) {
        const time = new Date(saved.savedAt).toLocaleString('pt-BR');
        const ok = confirm(
            `Encontrado ${saved.cells.length} célula(s) salva(s) em ${time}.\n\nRestaurar?`
        );
        if (ok) {
            restoreCells(saved.cells);
            saveIndicator.textContent = 'Restaurado — ' + time;
            saveIndicator.style.color = '#4fc3f7';
            isDirty = false;
        } else {
            // Usuário escolheu não restaurar — limpa o storage
            localStorage.removeItem(STORAGE_KEY);
        }
    }

    document.getElementById('cell-name-input').focus();
}

if (imgEl.complete && imgEl.naturalWidth) {
    init();
} else {
    imgEl.addEventListener('load', init);
}

window.addEventListener('resize', () => {
    // não refaz fitZoom automaticamente — preserva o zoom atual do usuário
});
