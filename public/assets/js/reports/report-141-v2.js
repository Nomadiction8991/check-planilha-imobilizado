/**
 * report-141-v2.js — JS extraído de src/Views/reports/report-141-v2.php
 * Detecção de edição, atualização em massa e validação de impressão.
 */
(function () {
    // Helper local para feedback visual Bootstrap
    function showFlash(type, msg) {
        var el = document.createElement('div');
        el.className = 'alert alert-' + type + ' alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow';
        el.style.zIndex = '1090';
        el.setAttribute('role', 'alert');
        el.innerHTML = msg + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        document.body.appendChild(el);
        setTimeout(function () { el.classList.remove('show'); setTimeout(function () { el.remove(); }, 300); }, 4000);
    }

    // Armazenar valores iniciais dos campos
    var valoresOriginais = new Map();

    document.addEventListener('DOMContentLoaded', function () {
        inicializarDeteccaoEdicao();
    });

    // Detectar edição manual em inputs e textareas
    function inicializarDeteccaoEdicao() {
        document.querySelectorAll('.a4 input[type="text"], .a4 textarea').forEach(function (campo) {
            valoresOriginais.set(campo.id, campo.value);

            campo.addEventListener('input', function () {
                var valorOriginal = valoresOriginais.get(this.id);
                if (this.value !== valorOriginal && this.value !== '') {
                    this.classList.add('editado');
                } else {
                    this.classList.remove('editado');
                }
            });
        });

        // Detectar checkboxes marcados
        document.querySelectorAll('.a4 input[type="checkbox"]').forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                if (this.checked) {
                    this.classList.add('marcado');
                } else {
                    this.classList.remove('marcado');
                }
            });
        });
    }

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
            if (!input.id.includes('geral')) {
                input.value = valor;
                if (valor !== '') {
                    input.classList.add('editado');
                }
            }
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

    // Validar e imprimir
    window.validarEImprimir = function () {
        var totalPaginas = document.querySelectorAll('.pagina-card').length;

        for (var i = 0; i < totalPaginas; i++) {
            var checks = document.querySelectorAll('.opcao-checkbox[data-page="' + i + '"]');
            var marcados = Array.from(checks).filter(function (c) { return c.checked; }).length;

            if (marcados !== 1) {
                showFlash('warning', 'Selecione exatamente 1 opção na página ' + (i + 1) + ' antes de imprimir.');
                // Rolar até a página com erro
                document.querySelectorAll('.pagina-card')[i].scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                return false;
            }
        }

        window.print();
    };
})();
