/**
 * report-141-new.js — JS extraído de src/Views/reports/report-141-new.php
 * Espera window.__totalPaginas definido via bridge PHP inline.
 */
(function () {
    var paginaAtual = 0;
    var totalPaginas = window.__totalPaginas || 0;

    window.navegarCarrossel = function (direcao) {
        paginaAtual += direcao;
        if (paginaAtual < 0) paginaAtual = 0;
        if (paginaAtual >= totalPaginas) paginaAtual = totalPaginas - 1;

        var track = document.getElementById('carrosselTrack');
        track.style.transform = 'translateX(-' + (paginaAtual * 100) + '%)';

        document.getElementById('paginaAtual').textContent = paginaAtual + 1;
        document.getElementById('btnPrev').disabled = paginaAtual === 0;
        document.getElementById('btnNext').disabled = paginaAtual === totalPaginas - 1;
    };

    // Inicializar botões
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('btnPrev').disabled = true;
        if (totalPaginas <= 1) document.getElementById('btnNext').disabled = true;
    });

    // ATUALIZAR todos os campos
    window.atualizarTodos = function (tipo) {
        var valor = document.getElementById(tipo + '_geral').value;
        var selector;
        switch (tipo) {
            case 'admin':
                selector = '[id^="administracao_"]';
                break;
            case 'cidade':
                selector = '[id^="cidade_"]';
                break;
            case 'setor':
                selector = '[id^="setor_"]';
                break;
            case 'admin_acessor':
                selector = '[id^="admin_acessor_"]';
                break;
            default:
                selector = '[id^="' + tipo + '_"]';
        }
        var inputs = document.querySelectorAll(selector);
        inputs.forEach(function (input) {
            if (!input.id.includes('geral')) input.value = valor;
        });
    };

    // Apenas 1 checkbox por página
    document.querySelectorAll('.opcao-checkbox').forEach(function (chk) {
        chk.addEventListener('change', function () {
            if (chk.checked) {
                var pageIndex = chk.dataset.page;
                document.querySelectorAll('.opcao-checkbox[data-page="' + pageIndex + '"]').forEach(function (other) {
                    if (other !== chk) other.checked = false;
                });
            }
        });
    });

    // Validar antes de imprimir
    window.addEventListener('beforeprint', function () {
        var pages = document.querySelectorAll('.carousel-page');
        for (var i = 0; i < pages.length; i++) {
            var checks = pages[i].querySelectorAll('.opcao-checkbox');
            var marcados = Array.from(checks).filter(function (c) { return c.checked; }).length;
            if (marcados !== 1) {
                alert('Selecione exatamente 1 opção na página ' + (i + 1) + ' antes de imprimir.');
                return false;
            }
        }
    });
})();
