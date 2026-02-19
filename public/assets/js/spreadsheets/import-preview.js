(() => {
    'use strict';

    const IMPORTACAO_ID = window._importacaoId;

    // ─── Coletar ações da página atual ───
    function coletarAcoesPagina() {
        const acoes = {};
        document.querySelectorAll('.select-acao').forEach(select => {
            const row = select.closest('tr');
            const linha = row?.dataset?.linha;
            if (linha) acoes[linha] = select.value;
        });
        // Incluir hidden inputs (erros)
        document.querySelectorAll('input[type="hidden"][name^="acao["]').forEach(input => {
            const match = input.name.match(/acao\[(\d+)\]/);
            if (match) acoes[match[1]] = input.value;
        });
        return acoes;
    }

    // ─── Salvar ações via AJAX ───
    async function salvarAcoes() {
        const acoes = coletarAcoesPagina();
        if (Object.keys(acoes).length === 0) return true;

        try {
            const resp = await fetch('/spreadsheets/preview/save-actions', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    importacao_id: IMPORTACAO_ID,
                    acoes: acoes
                })
            });
            const data = await resp.json();
            return data.sucesso === true;
        } catch (e) {
            console.error('Erro ao salvar ações:', e);
            return false;
        }
    }

    // ─── Salvar ações antes de navegar (paginação/filtros) ───
    window.salvarAcoesAntes = async function(e, url) {
        e.preventDefault();
        await salvarAcoes();
        window.location.href = url;
    };

    // ─── Salvar antes de confirmar ───
    window.salvarAntesDeConfirmar = function() {
        // As ações da página atual são enviadas pelo form normalmente
        // As ações anteriores já estão na sessão
        return true;
    };

    // ─── Atualizar estilo da linha conforme ação selecionada ───
    window.atualizarEstiloLinha = function(select) {
        const row = select.closest('tr');
        row.classList.remove('acao-pular', 'acao-excluir');

        if (select.value === 'pular') {
            row.classList.add('acao-pular');
        } else if (select.value === 'excluir') {
            row.classList.add('acao-excluir');
        }

        atualizarContadores();
    };

    // ─── Ação em massa — PÁGINA ATUAL ───
    window.acaoEmMassa = function(acao) {
        document.querySelectorAll('.registro-row').forEach(row => {
            const select = row.querySelector('.select-acao');
            if (!select) return;

            const opcao = select.querySelector(`option[value="${acao}"]`);
            if (opcao) {
                select.value = acao;
                atualizarEstiloLinha(select);
            }
        });
    };

    // ─── Ação em massa — TODOS OS REGISTROS (todas as páginas) ───
    window.acaoMassaTodos = async function(acao) {
        const label = acao === 'importar' ? 'IMPORTAR' : 'PULAR';
        if (!confirm(`Aplicar "${label}" a TODOS os registros de todas as páginas?`)) return;

        // Primeiro salva ações da página atual
        await salvarAcoes();

        try {
            const resp = await fetch('/spreadsheets/preview/bulk-action', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    importacao_id: IMPORTACAO_ID,
                    acao: acao
                })
            });
            const data = await resp.json();
            if (data.sucesso) {
                // Atualiza os selects da página atual para refletir
                acaoEmMassa(acao);
                alert(`${label} aplicado a ${data.total_aplicadas.toLocaleString()} registros.`);
            } else {
                alert('Erro: ' + (data.erro || 'Falha ao aplicar ação'));
            }
        } catch (e) {
            console.error('Erro ação em massa:', e);
            alert('Erro de conexão ao aplicar ação.');
        }
    };

    // ─── Contadores de ações (só da página atual) ───
    function atualizarContadores() {
        let importar = 0,
            pular = 0,
            excluir = 0;

        document.querySelectorAll('.select-acao').forEach(select => {
            switch (select.value) {
                case 'importar':
                    importar++;
                    break;
                case 'pular':
                    pular++;
                    break;
                case 'excluir':
                    excluir++;
                    break;
            }
        });

        document.querySelectorAll('input[type="hidden"][name^="acao"]').forEach(() => {
            pular++;
        });

        document.getElementById('contadores-acoes').innerHTML =
            `<strong class="text-success">${importar}</strong> importar · ` +
            `<strong class="text-secondary">${pular}</strong> pular · ` +
            `<strong class="text-danger">${excluir}</strong> excluir` +
            ` <span class="text-muted">(esta página)</span>`;
    }

    // ─── Confirmação antes de submeter ───
    document.getElementById('form-confirmar').addEventListener('submit', function(e) {
        let excluir = 0;
        document.querySelectorAll('.select-acao').forEach(select => {
            if (select.value === 'excluir') excluir++;
        });

        if (excluir > 0) {
            if (!confirm(`Atenção: ${excluir} produto(s) serão DESATIVADOS. Confirmar?`)) {
                e.preventDefault();
                return;
            }
        }
    });

    // ─── Controle por IGREJA (select-igreja) ───
    function aplicarAcaoPorComum(codigoComum, acao) {
        if (!codigoComum) return;
        document.querySelectorAll(`.registro-row[data-comum="${codigoComum}"]`).forEach(row => {
            const select = row.querySelector('.select-acao');
            if (!select) return;
            if (acao === '') return; // neutro — não altera linhas
            const opcao = select.querySelector(`option[value="${acao}"]`);
            if (opcao) {
                select.value = acao;
                atualizarEstiloLinha(select);
            }
        });
    }

    async function salvarIgrejas(igrejas) {
        try {
            const resp = await fetch('/spreadsheets/preview/save-actions', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ importacao_id: IMPORTACAO_ID, igrejas: igrejas })
            });
            const data = await resp.json();
            return data.sucesso === true;
        } catch (e) {
            console.error('Erro ao salvar igrejas:', e);
            return false;
        }
    }

    document.querySelectorAll('.select-igreja').forEach(sel => {
        sel.addEventListener('change', async function () {
            const codigo = this.dataset.codigo || '';
            const valor = this.value || '';
            aplicarAcaoPorComum(codigo, valor);
            await salvarIgrejas({ [codigo]: valor });
            atualizarContadores();
        });
    });

    // Inicializa contadores e estilos
    atualizarContadores();
    document.querySelectorAll('.select-acao').forEach(select => {
        atualizarEstiloLinha(select);
    });
})();
