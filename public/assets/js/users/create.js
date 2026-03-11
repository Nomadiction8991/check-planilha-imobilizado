// users/create.js — Vanilla JS (sem jQuery)

document.addEventListener('DOMContentLoaded', function () {
    aplicarMascaras();
    inicializarConjuge();
    inicializarRgIgualCpf();
    inicializarRgConjugeIgualCpf();
    inicializarBuscaCep();
    inicializarValidacaoFormulario();
});

// ── Máscaras com Inputmask ────────────────────────────────────────────────
function aplicarMascaras() {
    if (typeof Inputmask === 'undefined') return;

    var cpfMask  = '999.999.999-99';
    var rgMask   = '99.999.999-9';
    var telMask  = ['(99) 99999-9999', '(99) 9999-9999'];
    var cepMask  = '99999-999';

    ['cpf', 'cpf_conjuge'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) Inputmask(cpfMask).mask(el);
    });

    ['rg', 'rg_conjuge'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) Inputmask(rgMask).mask(el);
    });

    ['telefone', 'telefone_conjuge'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) Inputmask(telMask).mask(el);
    });

    var cepEl = document.getElementById('cep');
    if (cepEl) Inputmask(cepMask).mask(cepEl);
}

// ── Mostrar/ocultar dados do cônjuge ─────────────────────────────────────
function inicializarConjuge() {
    var casadoCb    = document.getElementById('casado');
    var cardConjuge = document.getElementById('cardConjuge');
    if (!casadoCb || !cardConjuge) return;

    var camposConjuge = ['nome_conjuge', 'cpf_conjuge', 'rg_conjuge', 'telefone_conjuge'];

    function toggleConjuge() {
        cardConjuge.style.display = casadoCb.checked ? '' : 'none';
        camposConjuge.forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) return;
            if (casadoCb.checked) {
                el.setAttribute('required', 'required');
            } else {
                el.removeAttribute('required');
            }
        });
    }

    casadoCb.addEventListener('change', toggleConjuge);
    toggleConjuge();
}

// ── RG igual ao CPF ───────────────────────────────────────────────────────
function inicializarRgIgualCpf() {
    var rgIgualCb = document.getElementById('rg_igual_cpf');
    var rgInput   = document.getElementById('rg');
    var cpfInput  = document.getElementById('cpf');
    if (!rgIgualCb || !rgInput) return;

    rgIgualCb.addEventListener('change', function () {
        if (this.checked) {
            var cpf = cpfInput ? cpfInput.value.replace(/\D/g, '') : '';
            rgInput.value = cpf;
            rgInput.setAttribute('readonly', 'readonly');
        } else {
            rgInput.value = '';
            rgInput.removeAttribute('readonly');
        }
    });
}

// ── RG cônjuge igual ao CPF cônjuge ──────────────────────────────────────
function inicializarRgConjugeIgualCpf() {
    var rgConjCb  = document.getElementById('rg_conjuge_igual_cpf');
    var rgConj    = document.getElementById('rg_conjuge');
    var cpfConj   = document.getElementById('cpf_conjuge');
    if (!rgConjCb || !rgConj) return;

    rgConjCb.addEventListener('change', function () {
        if (this.checked) {
            var cpf = cpfConj ? cpfConj.value.replace(/\D/g, '') : '';
            rgConj.value = cpf;
            rgConj.setAttribute('readonly', 'readonly');
        } else {
            rgConj.value = '';
            rgConj.removeAttribute('readonly');
        }
    });
}

// ── Busca de CEP via ViaCEP ──────────────────────────────────────────────
function inicializarBuscaCep() {
    var cepInput = document.getElementById('cep');
    if (!cepInput) return;

    cepInput.addEventListener('blur', function () {
        var cep = this.value.replace(/\D/g, '');
        if (cep.length !== 8) return;

        fetch('https://viacep.com.br/ws/' + cep + '/json/')
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.erro) {
                    mostrarFeedback('danger', 'CEP NÃO ENCONTRADO!');
                    return;
                }
                preencherCampo('logradouro', (data.logradouro || '').toUpperCase());
                preencherCampo('bairro',     (data.bairro    || '').toUpperCase());
                preencherCampo('cidade',     (data.localidade || '').toUpperCase());
                var estadoEl = document.getElementById('estado');
                if (estadoEl) estadoEl.value = data.uf || '';
                var numeroEl = document.getElementById('numero');
                if (numeroEl) numeroEl.focus();
            })
            .catch(function () {
                mostrarFeedback('danger', 'ERRO AO BUSCAR CEP!');
            });
    });
}

function preencherCampo(id, valor) {
    var el = document.getElementById(id);
    if (el) el.value = valor;
}

// ── Validação do formulário ───────────────────────────────────────────────
function inicializarValidacaoFormulario() {
    var form = document.getElementById('formUsuario');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        var senha    = document.getElementById('senha').value;
        var confirmar = document.getElementById('confirmar_senha').value;

        if (senha !== confirmar) {
            e.preventDefault();
            mostrarFeedback('danger', 'AS SENHAS NÃO COINCIDEM!');
            return false;
        }

        if (senha.length > 0 && senha.length < 6) {
            e.preventDefault();
            mostrarFeedback('danger', 'A SENHA DEVE TER NO MÍNIMO 6 CARACTERES!');
            return false;
        }
    });
}

// ── Helper: feedback Bootstrap inline ────────────────────────────────────
function mostrarFeedback(tipo, mensagem) {
    var el = document.createElement('div');
    el.className = 'alert alert-' + tipo + ' alert-dismissible fade show';
    el.setAttribute('role', 'alert');
    el.setAttribute('aria-live', 'polite');
    var icone = tipo === 'success' ? 'check-circle' : 'exclamation-triangle';
    el.innerHTML = '<i class="bi bi-' + icone + ' me-2"></i><span></span>'
        + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    el.querySelector('span').textContent = mensagem;

    var container = document.querySelector('.app-content .container-fluid')
        || document.querySelector('.app-content')
        || document.body;
    container.insertBefore(el, container.firstChild);
}
