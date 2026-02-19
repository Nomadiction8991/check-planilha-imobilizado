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
            const match = input.name.match(/acao\[([^\]]+)\]/);
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

    // ─── Salvar igrejas via AJAX ───
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

    // ─── Salvar ações antes de navegar (paginação) ───
    window.salvarAcoesAntes = async function(e, url) {
        e.preventDefault();
        await salvarAcoes();
        window.location.href = url;
    };

    // ─── Atualizar estilo da linha conforme ação selecionada ───
    window.atualizarEstiloLinha = function(select) {
        const row = select.closest('tr');
        row.classList.remove('acao-pular', 'acao-excluir');

        if (select.value === 'pular')   row.classList.add('acao-pular');
        if (select.value === 'excluir') row.classList.add('acao-excluir');

        atualizarContadores();
    };

    // ─── Importar Tudo (ignora seleções, processa tudo) ───
    window.importarTudo = async function () {
        if (!confirm('IMPORTAR TUDO: todos os registros serão processados ignorando qualquer seleção de igreja ou produto. Confirmar?')) return;
        await salvarAcoes();
        document.getElementById('importar_tudo_flag').value = '1';
        document.getElementById('form-confirmar').submit();
    };

    // ─── Contadores de ações (página atual) ───
    function atualizarContadores() {
        let importar = 0, pular = 0, excluir = 0;

        document.querySelectorAll('.select-acao').forEach(select => {
            switch (select.value) {
                case 'importar': importar++; break;
                case 'pular':    pular++;    break;
                case 'excluir':  excluir++;  break;
            }
        });

        document.querySelectorAll('input[type="hidden"][name^="acao"]').forEach(() => pular++);

        const el = document.getElementById('contadores-acoes');
        if (el) {
            el.innerHTML =
                `<strong class="text-success">${importar}</strong> importar · ` +
                `<strong class="text-secondary">${pular}</strong> n/importar · ` +
                `<strong class="text-danger">${excluir}</strong> excluir` +
                ` <span class="text-muted">(esta página)</span>`;
        }
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

    // ─── Evento: select-igreja muda → salva ações + igrejas e recarrega ───
    document.querySelectorAll('.select-igreja').forEach(sel => {
        sel.addEventListener('change', async function () {
            const codigo = this.dataset.codigo || '';
            const valor  = this.value || 'pular';
            // Salva ações de produtos da página atual antes de sair
            await salvarAcoes();
            // Salva escolha de igrejas
            await salvarIgrejas({ [codigo]: valor });
            // Reload com cache-busting para garantir dados frescos
            const url = new URL(window.location.href);
            url.searchParams.set('_t', Date.now());
            url.searchParams.delete('pagina');
            window.location.href = url.toString();
        });
    });

    // Evento: produto alterado manualmente → salva via AJAX
    document.querySelectorAll('.select-acao').forEach(select => {
        select.addEventListener('change', function () {
            atualizarEstiloLinha(this);
            atualizarContadores();
        });
    });

    // ─── Inicialização ───
    document.addEventListener('DOMContentLoaded', () => {
        atualizarContadores();
        document.querySelectorAll('.select-acao').forEach(select => atualizarEstiloLinha(select));
    });
})();
