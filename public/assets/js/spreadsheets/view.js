/* ==========================================================================
   spreadsheets/view.js
   Scripts extraídos de src/Views/spreadsheets/view.php
   ========================================================================== */

// ======== AÇÕES AJAX (check/etiqueta) ========
document.addEventListener('DOMContentLoaded', () => {
    // Fechar qualquer modal aberto que possa estar bloqueando a interação
    document.querySelectorAll('.modal.show').forEach(modal => {
        const bsModal = bootstrap.Modal.getInstance(modal);
        if (bsModal) {
            bsModal.hide();
        } else {
            modal.classList.remove('show');
            modal.style.display = 'none';
        }
    });
    // Remover backdrops persistentes
    document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
        backdrop.remove();
    });
    // Remover qualquer overlay que possa estar bloqueando
    document.querySelectorAll('.scanner-overlay').forEach(overlay => {
        overlay.remove();
    });

    const alertHost = document.createElement('div');
    alertHost.id = 'ajaxAlerts';
    alertHost.className = 'position-fixed top-0 start-50 translate-middle-x p-3';
    alertHost.style.zIndex = '1100';
    document.body.appendChild(alertHost);

    const showAlert = (type, message) => {
        const wrapper = document.createElement('div');
        wrapper.className = `alert alert-${type} alert-dismissible fade show shadow-sm`;
        wrapper.role = 'alert';
        wrapper.innerHTML = `
            <div class="d-flex align-items-center gap-2">
                <i class="bi ${type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'}"></i>
                <span>${message}</span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="FECHAR"></button>
        `;
        alertHost.appendChild(wrapper);
        setTimeout(() => {
            wrapper.classList.remove('show');
            wrapper.addEventListener('transitionend', () => wrapper.remove(), {
                once: true
            });
        }, 3000);
    };

    const linhaClasses = ['linha-dr', 'linha-imprimir', 'linha-checado', 'linha-observacao', 'linha-editado', 'linha-pendente'];
    const computeRowClass = (state) => {
        if (state.ativo === 0) return 'linha-dr';
        if (state.imprimir === 1) return 'linha-imprimir';
        if (state.checado === 1) return 'linha-checado';
        if ((state.observacao || '').trim() !== '') return 'linha-observacao';
        if (state.editado === 1) return 'linha-editado';
        return 'linha-pendente';
    };

    const getRowState = (row) => ({
        ativo: Number(row.dataset.ativo || 0),
        checado: Number(row.dataset.checado || 0),
        imprimir: Number(row.dataset.imprimir || 0),
        observacao: row.dataset.observacao || '',
        editado: Number(row.dataset.editado || 0)
    });

    const updateActionButtons = (row, state) => {
        // Todos os botes funcionam de forma INDEPENDENTE
        // Apenas bloquear quando produto estiver em DR (ativo=0)
        const active = state.ativo === 1;

        const checkActive = state.checado === 1;
        const checkDisabled = !active; // S bloqueia se DR

        const imprimirActive = state.imprimir === 1;
        const imprimirDisabled = !active; // S bloqueia se DR

        // Check
        row.querySelectorAll('.action-check').forEach(el => {
            el.style.display = 'inline-block';
            const btn = el.querySelector('button');
            const checkForm = row.querySelector('.PRODUTO-action-form.action-check');
            const checkInput = checkForm ? checkForm.querySelector('input[name="checado"]') : null;
            if (btn) {
                btn.disabled = checkDisabled;
                btn.classList.toggle('active', checkActive);
                if (checkDisabled) {
                    btn.setAttribute('aria-disabled', 'true');
                } else {
                    btn.removeAttribute('aria-disabled');
                }
                btn.title = checkActive ? 'Desmarcar checado' : 'Marcar como checado';
            }
            if (checkInput) {
                checkInput.value = checkActive ? '0' : '1';
            }
        });

        // Imprimir
        row.querySelectorAll('.action-imprimir').forEach(el => {
            el.style.display = 'inline-block';
            const btn = el.querySelector('button');
            const imprimirFormEl = row.querySelector('.PRODUTO-action-form.action-imprimir');
            const imprimirInput = imprimirFormEl ? imprimirFormEl.querySelector('input[name="imprimir"]') : null;
            if (btn) {
                btn.disabled = imprimirDisabled;
                btn.classList.toggle('active', imprimirActive);
                btn.classList.remove('disabled-visually');
                if (imprimirDisabled) {
                    btn.setAttribute('aria-disabled', 'true');
                } else {
                    btn.removeAttribute('aria-disabled');
                }
                btn.title = imprimirActive ? 'Remover etiqueta' : 'Marcar para etiqueta';
            }
            if (imprimirInput) {
                imprimirInput.value = imprimirActive ? '0' : '1';
            }
        });

        // Observação - sempre disponvel
        row.querySelectorAll('.btn-outline-warning').forEach(el => {
            el.style.display = 'inline-block';
            el.classList.remove('disabled');
            el.removeAttribute('aria-disabled');
        });

        // Editar - sempre disponvel
        row.querySelectorAll('.btn-outline-primary').forEach(el => {
            el.style.display = 'inline-block';
            el.classList.remove('disabled-visually');
            el.removeAttribute('aria-disabled');
        });
    };

    const applyState = (row, updates = {}) => {
        const state = {
            ...getRowState(row),
            ...updates
        };
        // NO forar nenhum estado - cada botão  independente
        row.dataset.ativo = state.ativo;
        row.dataset.checado = state.checado;
        row.dataset.imprimir = state.imprimir;
        row.dataset.observacao = state.observacao ?? '';
        row.dataset.editado = state.editado ?? row.dataset.editado;

        linhaClasses.forEach(c => row.classList.remove(c));
        row.classList.add(computeRowClass(state));
        updateActionButtons(row, state);
    };

    document.querySelectorAll('.list-group-item[data-produto-id]').forEach(row => {
        updateActionButtons(row, getRowState(row));
    });


    // Clique em EDITAR: no marcar como checado automaticamente  permitir que a edição seja feita e s marcar ao salvar
    document.addEventListener('click', function(ev) {
        const a = ev.target.closest && ev.target.closest('.action-editar');
        if (!a) return;
        // Se estiver visualmente desabilitado, ignorar
        if (a.classList.contains('disabled') || a.getAttribute('aria-disabled') === 'true') return;
        // Permitir comportamento padrão (navegação para a página de edição)
        // A marcao como 'checado' ser tratada ao salvar as alteraes no servidor (ProdutoUpdateController)
    });

    // Observer removido - cada botão funciona de forma independente
    document.querySelectorAll('.PRODUTO-action-form').forEach(form => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const action = form.dataset.action;
            const PRODUTOId = form.dataset.produtoId;
            const confirmMsg = form.dataset.confirm;
            if (confirmMsg && !confirm(confirmMsg)) {
                return;
            }

            const formData = new FormData(form);

            // Sincronizar o valor dos inputs escondidos antes do submit (redundante, mas garante consistncia)
            if (action === 'imprimir') {
                const imprimirInput = form.querySelector('input[name="imprimir"]');
                if (imprimirInput) {
                    // garantir valor coerente (j  atualizado em outros pontos do script)
                    imprimirInput.value = imprimirInput.value;
                }
            }
            if (action === 'check') {
                const checkInput = form.querySelector('input[name="checado"]');
                if (checkInput) checkInput.value = checkInput.value;
            }

            fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(async response => {
                    let data = {};
                    try {
                        data = await response.json();
                    } catch (e) {
                        // resposta n£o era JSON
                    }
                    if (!response.ok || data.success === false) {
                        throw new Error(data.message || 'NO FOI POSSVEL ATUALIZAR.');
                    }
                    return data;
                })
                .then(data => {

                    const row = document.querySelector(`.list-group-item[data-produto-id="${PRODUTOId}"]`);
                    const stateUpdates = {};

                    if (action === 'check') {
                        const newVal = Number(formData.get('checado') || 0);
                        stateUpdates.checado = newVal;
                        const input = form.querySelector('input[name=\"checado\"]');
                        if (input) {
                            input.value = newVal ? '0' : '1';
                        }
                        const btn = form.querySelector('button');
                        if (btn) {
                            btn.classList.toggle('active', newVal === 1);
                        }
                    } else if (action === 'imprimir') {
                        const newVal = Number(formData.get('imprimir') || 0);
                        stateUpdates.imprimir = newVal;
                        const input = form.querySelector('input[name="imprimir"]');
                        if (input) {
                            input.value = newVal ? '0' : '1';
                        }
                        const btn = form.querySelector('button');
                        if (btn) {
                            btn.classList.toggle('active', newVal === 1);
                        }
                    }

                    if (row) {
                        applyState(row, stateUpdates);
                    }

                    showAlert('success', (data.message || 'STATUS ATUALIZADO COM SUCESSO').toUpperCase());
                })
                .catch(err => {
                    showAlert('danger', (err.message || 'ERRO AO PROCESSAR AO').toUpperCase());
                });
        });
    });

    // Observação via modal + AJAX
    (function setupObservacao() {
        const modalEl = document.getElementById('observacaoModal');
        if (!modalEl) return;
        const obsModal = new bootstrap.Modal(modalEl, {
            backdrop: 'static',
            keyboard: false
        });
        const ta = modalEl.querySelector('#observacaoText');
        const saveBtn = modalEl.querySelector('#observacaoSaveBtn');
        let current = null; // {row, prodId, comumId, anchor}

        function openModalFor(anchor) {
            if (!anchor) return;
            if (anchor.classList.contains('disabled') || anchor.getAttribute('aria-disabled') === 'true') return;
            const prodId = anchor.dataset.produtoId || anchor.closest('.list-group-item')?.dataset.produtoId;
            const comumId = anchor.dataset.comumId || window._comumId;
            const row = document.querySelector(`.list-group-item[data-produto-id="${prodId}"]`);
            const curObs = row ? (row.dataset.observacao || '') : '';
            ta.value = curObs;
            current = {
                row,
                prodId,
                comumId,
                anchor
            };
            obsModal.show();
            ta.focus();
        }

        // Restaurar comportamento original: clique em Observação navega para a página de observação (no abrir modal).
        // Se o link estiver desabilitado, impedir a navegação.
        document.querySelectorAll('.action-observacao').forEach(a => {
            a.addEventListener('click', function(ev) {
                if (a.classList.contains('disabled') || a.getAttribute('aria-disabled') === 'true') {
                    ev.preventDefault();
                    return;
                }
                // Permitir comportamento padrão: navegador seguir o href para a página de observação.
            });
        });

        saveBtn.addEventListener('click', function() {
            if (!current) return;
            saveBtn.disabled = true;
            const formData = new FormData();
            formData.set('id_produto', current.prodId);
            formData.set('comum_id', current.comumId);
            formData.set('observacoes', ta.value.trim()); // controller expects 'observacoes'

            fetch('/products/observation', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            }).then(async resp => {
                let data = {};
                try {
                    data = await resp.json();
                } catch (e) {}
                if (!resp.ok || data.success === false) throw new Error(data.message || 'Falha ao salvar observação');
                // Atualizar UI
                const newObs = ta.value.trim();
                if (current.row) {
                    applyState(current.row, {
                        observacao: newObs
                    });
                }
                current.anchor.classList.toggle('active', newObs !== '');
                showAlert('success', data.message || 'Observação atualizada');
                obsModal.hide();
            }).catch(err => {
                showAlert('danger', (err.message || 'Erro ao salvar observação').toUpperCase());
            }).finally(() => {
                saveBtn.disabled = false;
            });
        });
    })();
});

// ======== RECONHECIMENTO DE VOZ ========
(() => {
    const POSSIVEIS_IDS_INPUT = ["cod", "codigo", "code", "productCode", "busca", "search", "q"];

    function encontraInputCodigo() {
        for (const id of POSSIVEIS_IDS_INPUT) {
            const el = document.getElementById(id);
            if (el) return el;
        }
        for (const name of ["cod", "codigo", "code", "productCode", "q", "busca", "search"]) {
            const el = document.querySelector(`input[name="${name}"]`);
            if (el) return el;
        }
        const el = document.querySelector('input[placeholder*="código" i],input[placeholder*="codigo" i]');
        return el || null;
    }

    function encontraBotaoPesquisar(input) {
        if (input && input.form) {
            const b = input.form.querySelector('button[type="submit"],input[type="submit"]');
            if (b) return b;
        }
        return document.querySelector('button[type="submit"],input[type="submit"]');
    }

    let micBtn = document.getElementById('btnMic');
    if (!micBtn) return;

    const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SR) {
        micBtn.setAttribute('aria-disabled', 'true');
        micBtn.title = 'Reconhecimento de voz não suportado neste navegador';
        const iconNF = micBtn.querySelector('.material-icons-round');
        if (iconNF) {
            iconNF.textContent = 'mic_off';
        }
        micBtn.addEventListener('click', () => {
            alert('Reconhecimento de voz não suportado neste navegador. Use o botão de câmera ou digite o código.');
        });
        return;
    }

    const DIGITOS = {
        "zero": "0",
        "um": "1",
        "uma": "1",
        "dois": "2",
        "duas": "2",
        "três": "3",
        "tres": "3",
        "quatro": "4",
        "cinco": "5",
        "seis": "6",
        "meia": "6",
        "sete": "7",
        "oito": "8",
        "nove": "9"
    };
    const SINAIS = {
        "tracinho": "-",
        "hífen": "-",
        "hifen": "-",
        "menos": "-",
        "barra": "/",
        "barra invertida": "\\",
        "contrabarra": "\\",
        "invertida": "\\",
        "ponto": ".",
        "vírgula": ",",
        "virgula": ",",
        "espaço": " "
    };

    function extraiCodigoFalado(trans) {
        let direto = trans.replace(/[^\d\-./,\\ ]+/g, '').trim();
        direto = direto.replace(/\s+/g, '');
        if (/\d/.test(direto)) return direto;

        const out = [];
        for (const raw of trans.toLowerCase().split(/\s+/)) {
            const w = raw.normalize('NFD').replace(/\p{Diacritic}/gu, '');
            if (DIGITOS[w]) out.push(DIGITOS[w]);
            else if (SINAIS[w]) out.push(SINAIS[w]);
            else if (/^\d+$/.test(w)) out.push(w);
        }
        return out.join('');
    }

    async function preencherEEnviar(codigo) {
        const input = encontraInputCodigo();
        if (!input) {
            alert('Campo de código não encontrado.');
            return;
        }
        input.focus();
        input.value = codigo;
        input.dispatchEvent(new Event('input', {
            bubbles: true
        }));
        input.dispatchEvent(new Event('change', {
            bubbles: true
        }));

        const btn = encontraBotaoPesquisar(input);
        if (btn) {
            btn.click();
            return;
        }
        if (input.form) {
            input.form.requestSubmit ? input.form.requestSubmit() : input.form.submit();
            return;
        }
        const ev = new KeyboardEvent('keydown', {
            key: 'Enter',
            code: 'Enter',
            bubbles: true
        });
        input.dispatchEvent(ev);
    }

    const rec = new SR();
    rec.lang = 'pt-BR';
    rec.continuous = false;
    rec.interimResults = false;
    rec.maxAlternatives = 3;

    function setMicIcon(listening) {
        const icon = micBtn.querySelector('.material-icons-round');
        if (icon) {
            icon.textContent = listening ? 'graphic_eq' : 'mic';
        }
    }

    function startListening() {
        try {
            rec.start();
            micBtn.classList.add('listening');
            micBtn.setAttribute('aria-pressed', 'true');
            setMicIcon(true);
        } catch (e) {}
    }

    function stopListening() {
        try {
            rec.stop();
        } catch (e) {}
        micBtn.classList.remove('listening');
        micBtn.setAttribute('aria-pressed', 'false');
        setMicIcon(false);
    }

    rec.onresult = (e) => {
        const best = e.results[0][0].transcript || '';
        const codigo = extraiCodigoFalado(best);
        stopListening();
        if (!codigo) {
            alert('No entendi o código. Tente soletrar: "um dois trs"');
            return;
        }
        preencherEEnviar(codigo);
    };

    rec.onerror = (e) => {
        stopListening();
        if (e.error === 'not-allowed') alert('Permita o acesso ao microfone para usar a busca por voz.');
    };

    rec.onend = () => micBtn.classList.remove('listening');

    micBtn.addEventListener('click', () => {
        if (micBtn.classList.contains('listening')) stopListening();
        else startListening();
    });

    document.addEventListener('keydown', (ev) => {
        if ((ev.ctrlKey || ev.metaKey) && ev.key.toLowerCase() === 'm') {
            ev.preventDefault();
            micBtn.click();
        }
    });
})();


// ======== CÂMERA / BARCODE SCANNER (Quagga2) ========

// Aguardar TUDO carregar (DOM + Bootstrap + Quagga)
document.addEventListener('DOMContentLoaded', function() {
    // Aguardar mais um pouco para garantir que Bootstrap está pronto
    setTimeout(initFloatingButtons, 100);
    setTimeout(initBarcodeScanner, 500);
});

// ===== INICIALIZAÇÃO DOS BOTÕES FLUTUANTES =====
function initFloatingButtons() {
    console.log('===== INICIALIZANDO BOTÕES FLUTUANTES =====');

    const btnFloatingMic = document.getElementById('btnFloatingMic');
    const btnFloatingCam = document.getElementById('btnFloatingCam');
    const btnMicOriginal = document.getElementById('btnMic');
    const btnCamOriginal = document.getElementById('btnCam');
    const cameraFullscreenModal = document.getElementById('cameraFullscreenModal');
    const cameraCloseBtn = document.getElementById('cameraCloseBtn');

    if (!btnFloatingMic || !btnFloatingCam) {
        console.error('Botões flutuantes não encontrados!');
        return;
    }

    // Conectar botão flutuante de microfone ao botão original
    if (btnMicOriginal) {
        btnFloatingMic.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Clicado botão flutuante de microfone');
            btnMicOriginal.click();
        });
        console.log('✓ Botão flutuante de microfone conectado');
    }

    // Conectar botão flutuante de câmera para abrir modal fullscreen
    if (btnFloatingCam && cameraFullscreenModal) {
        btnFloatingCam.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Clicado botão flutuante de câmera');
            cameraFullscreenModal.classList.add('show');

            // Aguardar ser visível antes de iniciar câmera
            setTimeout(initFullscreenCamera, 300);
        });
        console.log('✓ Botão flutuante de câmera conectado');
    }

    // Botão de fechar da modal fullscreen
    if (cameraCloseBtn && cameraFullscreenModal) {
        cameraCloseBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Clicado botão de fechar câmera');
            stopFullscreenCamera();
            cameraFullscreenModal.classList.remove('show');
        });
    }

    // Fechar ao clicar fora (backdrop)
    cameraFullscreenModal.addEventListener('click', function(e) {
        if (e.target === cameraFullscreenModal) {
            stopFullscreenCamera();
            cameraFullscreenModal.classList.remove('show');
        }
    });

    // ESC para fechar
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && cameraFullscreenModal.classList.contains('show')) {
            stopFullscreenCamera();
            cameraFullscreenModal.classList.remove('show');
        }
    });

    console.log('✓ Botões flutuantes inicializados com sucesso!');
}

// ===== VARIÁVEIS GLOBAIS DA CÂMERA FULLSCREEN =====
let fullscreenScanning = false;
let fullscreenCurrentStream = null;
let fullscreenCurrentTrack = null;
let fullscreenSelectedDeviceId = '';
let fullscreenAvailableCameras = [];
let fullscreenLastCode = '';

async function initFullscreenCamera() {
    console.log('Inicializando câmera fullscreen...');

    await enumerateFullscreenCameras();
    startFullscreenScanner();
}

async function enumerateFullscreenCameras() {
    const cameraSelectFloating = document.getElementById('cameraSelectFloating');
    if (!cameraSelectFloating) return;

    try {
        const devices = await navigator.mediaDevices.enumerateDevices();
        fullscreenAvailableCameras = devices.filter(device => device.kind === 'videoinput');

        console.log(`√ ${fullscreenAvailableCameras.length} câmera(s) encontrada(s)`);

        cameraSelectFloating.innerHTML = '<option value="">Câmera padrão</option>';
        fullscreenAvailableCameras.forEach((camera, index) => {
            const option = document.createElement('option');
            option.value = camera.deviceId;
            option.text = camera.label || `Câmera ${index + 1}`;
            cameraSelectFloating.appendChild(option);
        });
    } catch (err) {
        console.error('Erro ao enumerar câmeras:', err);
    }
}

function startFullscreenScanner() {
    if (fullscreenScanning) return;
    fullscreenScanning = true;
    fullscreenLastCode = '';

    const container = document.getElementById('cameraFullscreenContainer');
    if (!container) return;

    const constraints = {
        width: {
            ideal: 1920
        },
        height: {
            ideal: 1080
        }
    };

    if (fullscreenSelectedDeviceId) {
        constraints.deviceId = {
            exact: fullscreenSelectedDeviceId
        };
    } else {
        constraints.facingMode = 'environment';
    }

    Quagga.init({
        inputStream: {
            type: 'LiveStream',
            target: container,
            constraints: constraints
        },
        decoder: {
            readers: [
                'ean_reader',
                'code_128_reader',
                'ean_8_reader',
                'upc_reader',
                'upc_e_reader'
            ],
            multiple: false
        },
        locate: true,
        locator: {
            patchSize: 'large',
            halfSample: true
        },
        frequency: 10,
        numOfWorkers: navigator.hardwareConcurrency || 4
    }, function(err) {
        if (err) {
            console.error('Erro ao iniciar câmera:', err);
            alert('Não foi possível acessar a câmera:\n\n' + err.message);
            fullscreenScanning = false;
            return;
        }

        console.log('√ Câmera fullscreen iniciada!');
        Quagga.start();

        // Capturar stream
        const videoElement = container.querySelector('video');
        if (videoElement && videoElement.srcObject) {
            fullscreenCurrentStream = videoElement.srcObject;
            const videoTracks = fullscreenCurrentStream.getVideoTracks();
            if (videoTracks.length > 0) {
                fullscreenCurrentTrack = videoTracks[0];
            }
        }
    });

    Quagga.offDetected();
    Quagga.onDetected(function(result) {
        if (!result || !result.codeResult || !result.codeResult.code) return;

        const rawCode = result.codeResult.code.trim();
        if (!rawCode || rawCode === fullscreenLastCode) return;

        fullscreenLastCode = rawCode;
        const code = normalizeBarcodeFullscreen(rawCode);

        console.log('√ Código detectado:', code);

        // Feedback visual
        const frame = document.getElementById('scannerFrameFullscreen');
        if (frame) {
            frame.classList.add('detected');
            setTimeout(() => frame.classList.remove('detected'), 300);
        }

        stopFullscreenScanner();
        const cameraModal = document.getElementById('cameraFullscreenModal');
        if (cameraModal) cameraModal.classList.remove('show');

        // Preencher o input original e enviar
        const codigoInput = document.getElementById('codigo');
        if (codigoInput) {
            codigoInput.value = code;
            codigoInput.dispatchEvent(new Event('input', {
                bubbles: true
            }));
            codigoInput.dispatchEvent(new Event('change', {
                bubbles: true
            }));

            const form = codigoInput.form || document.querySelector('form');
            if (form) {
                form.requestSubmit ? form.requestSubmit() : form.submit();
            }
        }
    });
}

function stopFullscreenScanner() {
    console.log('Parando câmera fullscreen...');
    try {
        Quagga.stop();
        if (fullscreenCurrentStream) {
            fullscreenCurrentStream.getTracks().forEach(track => track.stop());
            fullscreenCurrentStream = null;
        }
        fullscreenCurrentTrack = null;

        const container = document.getElementById('cameraFullscreenContainer');
        if (container) {
            while (container.firstChild) {
                container.removeChild(container.firstChild);
            }
        }
    } catch (e) {
        console.error('Erro ao parar câmera:', e);
    }
    fullscreenScanning = false;
}

function normalizeBarcodeFullscreen(code) {
    return code.trim().replace(/\s+/g, '');
}

// Eventos de câmera
document.getElementById('cameraSelectFloating')?.addEventListener('change', function(e) {
    fullscreenSelectedDeviceId = e.target.value;
    if (fullscreenScanning) {
        stopFullscreenScanner();
        setTimeout(() => startFullscreenScanner(), 300);
    }
});

document.getElementById('zoomSliderFloating')?.addEventListener('input', function(e) {
    const zoomLevel = parseFloat(e.target.value);
    document.getElementById('zoomLevelFloating').textContent = zoomLevel.toFixed(1) + 'x';
    if (fullscreenCurrentTrack && fullscreenCurrentTrack.getCapabilities().zoom) {
        fullscreenCurrentTrack.applyConstraints({
            advanced: [{
                zoom: zoomLevel
            }]
        });
    }
});

function stopFullscreenCamera() {
    stopFullscreenScanner();
}

// ===== FUNÇÃO DUMMY PARA COMPATIBILIDADE =====
function initBarcodeScanner() {
    console.log('initBarcodeScanner() chamada - usando câmera fullscreen');
    // Compatibilidade com código antigo - não fazer nada
    // Os botões flutuantes já controlam tudo
}
