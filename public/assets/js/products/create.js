// DEPENDÊNCIA do select "Bem" em FUNÇÃO do "Tipos de Bens"
const selectTipoBen = document.getElementById('id_tipo_ben');
const selectBem = document.getElementById('tipo_ben');

function separarOpcoesPorBarra(descricao) {
    return descricao.split('/').map(item => item.trim()).filter(item => item !== '');
}

function atualizarOpcoesBem() {
    const selectedOption = selectTipoBen.options[selectTipoBen.selectedIndex];
    const descricao = selectedOption ? (selectedOption.getAttribute('data-descricao') || '') : '';
    selectBem.innerHTML = '';
    if (selectTipoBen.value && descricao) {
        const opcoes = separarOpcoesPorBarra(descricao);
        const optionPadrao = document.createElement('option');
        optionPadrao.value = '';
        optionPadrao.textContent = 'SELECIONE UM BEM';
        selectBem.appendChild(optionPadrao);
        opcoes.forEach(opcao => {
            const option = document.createElement('option');
            option.value = opcao;
            option.textContent = opcao;
            const _postTipoBen = window._appConfig ? window._appConfig.postTipoBen : null;
            const _postIdTipoBen = window._appConfig ? window._appConfig.postIdTipoBen : null;
            if (_postTipoBen !== null && _postIdTipoBen !== null) {
                if (opcao === _postTipoBen && selectTipoBen.value === String(_postIdTipoBen)) {
                    option.selected = true;
                }
            }
            selectBem.appendChild(option);
        });
        selectBem.disabled = false;
    } else {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'PRIMEIRO SELECIONE UM TIPO DE BEM';
        selectBem.appendChild(option);
        selectBem.disabled = true;
    }
}

selectTipoBen.addEventListener('change', atualizarOpcoesBem);
document.addEventListener('DOMContentLoaded', atualizarOpcoesBem);

// Validação Bootstrap
(() => {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();

// Controle dos radios de condição 14.1
(function () {
    var imprimir = document.getElementById('imprimir_14_1');
    var radios = Array.from(document.querySelectorAll('input[name="condicao_14_1"]'));

    function ensureDefault() {
        if (!radios.some(function (r) { return r.checked; })) {
            var def = document.getElementById('condicao_141_2');
            if (def) def.checked = true;
        }
    }

    function updateRequirement() {
        var required = imprimir && imprimir.checked;
        radios.forEach(function (r) {
            if (required) r.setAttribute('required', 'required');
            else r.removeAttribute('required');
        });
        ensureDefault();
    }

    ensureDefault();
    if (imprimir) imprimir.addEventListener('change', updateRequirement);
}());
