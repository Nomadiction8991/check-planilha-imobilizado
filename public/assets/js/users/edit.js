// ========== MÁSCARAS COM INPUTMASK (PROTEGIDAS) ==========
(function() {
    function initEditUserForm() {
        try {
            // Se a biblioteca de Inputmask não estiver carregada, ignorar as máscaras
            if (typeof Inputmask !== 'undefined') {
                Inputmask('999.999.999-99').mask('#cpf');
                Inputmask('999.999.999-99').mask('#cpf_conjuge');
                Inputmask(['(99) 99999-9999', '(99) 9999-9999']).mask('#telefone');
                Inputmask(['(99) 99999-9999', '(99) 9999-9999']).mask('#telefone_conjuge');
                Inputmask('99999-999').mask('#cep');
            }

            // RG dinâmico (aplica apenas se os elementos existirem)
            function formatRgDigits(raw) {
                const digits = raw.replace(/\D/g, '');
                if (digits.length <= 1) return digits;
                return digits.slice(0, -1) + '-' + digits.slice(-1);
            }

            const rgInput = document.getElementById('rg');
            if (rgInput) {
                const rgIgualEl = document.getElementById('rg_igual_cpf');
                const cpfEl = document.getElementById('cpf');

                function aplicarRgIgualCpf(aplicar) {
                    if (aplicar) {
                        if (typeof Inputmask !== 'undefined') Inputmask('999.999.999-99').mask('#rg');
                        rgInput.value = cpfEl ? cpfEl.value : rgInput.value;
                        rgInput.setAttribute('disabled', 'disabled');
                    } else {
                        rgInput.removeAttribute('disabled');
                        if (typeof Inputmask !== 'undefined') Inputmask.remove('#rg');
                        rgInput.value = formatRgDigits(window._editUserRgDigits || '');
                    }
                }
                if (rgIgualEl) rgInput.addEventListener('input', function() {
                    if (!rgIgualEl.checked) this.value = formatRgDigits(this.value);
                });
                if (rgIgualEl) rgIgualEl.addEventListener('change', function() {
                    aplicarRgIgualCpf(this.checked);
                });
                if (cpfEl) cpfEl.addEventListener('input', function() {
                    if (rgIgualEl && rgIgualEl.checked) aplicarRgIgualCpf(true);
                });
                aplicarRgIgualCpf(rgIgualEl ? rgIgualEl.checked : false);
            }

            const rgConjInput = document.getElementById('rg_conjuge');
            if (rgConjInput) {
                const rgConjIgualEl = document.getElementById('rg_conjuge_igual_cpf');
                const cpfConjEl = document.getElementById('cpf_conjuge');

                function aplicarRgConjugeIgualCpf(aplicar) {
                    if (aplicar) {
                        if (typeof Inputmask !== 'undefined') Inputmask('999.999.999-99').mask('#rg_conjuge');
                        rgConjInput.value = cpfConjEl ? cpfConjEl.value : rgConjInput.value;
                        rgConjInput.setAttribute('disabled', 'disabled');
                    } else {
                        rgConjInput.removeAttribute('disabled');
                        if (typeof Inputmask !== 'undefined') Inputmask.remove('#rg_conjuge');
                        rgConjInput.value = formatRgDigits(window._editUserRgConjugeDigits || '');
                    }
                }
                if (rgConjIgualEl) rgConjInput.addEventListener('input', function() {
                    if (!rgConjIgualEl.checked) this.value = formatRgDigits(this.value);
                });
                if (rgConjIgualEl) rgConjIgualEl.addEventListener('change', function() {
                    aplicarRgConjugeIgualCpf(this.checked);
                });
                if (cpfConjEl) cpfConjEl.addEventListener('input', function() {
                    if (rgConjIgualEl && rgConjIgualEl.checked) aplicarRgConjugeIgualCpf(true);
                });
                aplicarRgConjugeIgualCpf(rgConjIgualEl ? rgConjIgualEl.checked : false);
            }

            // Toggle do bloco do cônjuge (IIFE garante execução isolada)
            (function() {
                const casadoCb = document.getElementById('casado');
                const card = document.getElementById('cardConjuge');
                if (!casadoCb || !card) return;

                function setRequiredOnConjuge(aplicar) {
                    const ids = ['nome_conjuge', 'cpf_conjuge', 'telefone_conjuge'];
                    ids.forEach(id => {
                        const el = document.getElementById(id);
                        if (!el) return;
                        if (aplicar) {
                            el.setAttribute('required', 'required');
                        } else {
                            el.removeAttribute('required');
                        }
                    });

                    for (const id of ids) {
                        const label = document.querySelector('label[for="' + id + '"]');
                        if (!label) continue;
                        if (aplicar) {
                            if (!label.querySelector('.required-asterisk')) {
                                const span = document.createElement('span');
                                span.className = 'text-danger required-asterisk ms-1';
                                span.textContent = '*';
                                label.appendChild(span);
                            }
                        } else {
                            const star = label.querySelector('.required-asterisk');
                            if (star) star.remove();
                        }
                    }
                }

                const setVisibility = () => {
                    card.style.display = casadoCb.checked ? '' : 'none';
                    setRequiredOnConjuge(casadoCb.checked);
                    try {
                        console.debug('usuario_editar: casado = ' + (casadoCb.checked ? 'true' : 'false'));
                    } catch (e) {}
                };
                casadoCb.addEventListener('change', setVisibility);
                setVisibility();
            })();

        } catch (e) {
            console.error('Erro ao inicializar máscaras/inputs em usuario_editar:', e);
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEditUserForm);
    } else {
        initEditUserForm();
    }
})();

// ========== VIACEP: BUSCA AUTOMÁTICA DE ENDEREÇO ==========
document.getElementById('cep').addEventListener('blur', function() {
    const cep = this.value.replace(/\D/g, '');

    if (cep.length !== 8) return;

    // LIMPAR campos antes de buscar
    document.getElementById('logradouro').value = 'Buscando...';
    document.getElementById('bairro').value = '';
    document.getElementById('cidade').value = '';
    document.getElementById('estado').value = '';

    fetch(`https://viacep.com.br/ws/${cep}/json/`)
        .then(response => response.json())
        .then(data => {
            if (data.erro) {
                showFlash('danger', 'CEP NÃO ENCONTRADO!');
                document.getElementById('logradouro').value = '';
                return;
            }

            document.getElementById('logradouro').value = data.logradouro || '';
            document.getElementById('bairro').value = data.bairro || '';
            document.getElementById('cidade').value = data.localidade || '';
            document.getElementById('estado').value = data.uf || '';

            // Focar no número após preencher
            document.getElementById('numero').focus();
        })
        .catch(error => {
            console.error('Erro ao buscar CEP:', error);
            showFlash('danger', 'ERRO AO BUSCAR CEP. TENTE NOVAMENTE.');
            document.getElementById('logradouro').value = '';
        });
});

// ========== ASSINATURA DIGITAL (PADRÃO MODAL) ==========
// Variáveis globais
// Assinatura JS removida: funcionalidades e manipulações de DOM para captura/preview de assinaturas foram removidas.
// Mantivemos apenas um comportamento de 'readonly' para visualização quando não é o próprio usuário.

// Funções de assinatura removidas. Mantemos stubs para evitar erros caso chamadas permaneçam em código legado.
window.limparModalAssinatura = function() {
    /* assinatura removida */
};
window.salvarModalAssinatura = function() {
    /* assinatura removida */
};
window.fecharModalAssinatura = async function() {
    /* assinatura removida */
};

// Se NÃO é o próprio usuário, desabilitar todos campos
document.addEventListener('DOMContentLoaded', function() {
    // Placeholder para futuras validações de permissão, se necessário
});

// ========== HELPER: showFlash (local copy) ==========
function showFlash(type, message) {
    const el = document.createElement('div');
    el.className = 'alert alert-' + type + ' alert-dismissible fade show';
    el.setAttribute('role', 'alert');
    const icon = (type === 'success') ? 'check-circle' : 'exclamation-triangle';
    el.innerHTML = '<i class="bi bi-' + icon + ' me-2"></i><span></span><button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    el.querySelector('span').textContent = message;
    const container = document.querySelector('.app-content .container-fluid') || document.querySelector('.app-content') || document.body;
    container.insertBefore(el, container.firstChild);
}

// ========== VALIDAÇÃO E ENVIO DO FORMULÁRIO ==========
document.getElementById('formUsuario').addEventListener('submit', function(e) {
    const senha = document.getElementById('senha').value;
    const confirmar = document.getElementById('confirmar_senha').value;

    // Validação senhas (somente se preenchidas)
    if (senha || confirmar) {
        if (senha !== confirmar) {
            e.preventDefault();
            showFlash('danger', 'AS SENHAS NÃO CONFEREM!');
            return false;
        }
    }
    // Endereço obrigatório
    const enderecoObrigatorios = ['cep', 'logradouro', 'numero', 'bairro', 'cidade', 'estado'];
    for (let id of enderecoObrigatorios) {
        const el = document.getElementById(id);
        if (!el.value.trim()) {
            e.preventDefault();
            showFlash('danger', 'TODOS OS CAMPOS DE ENDEREÇO SÃO OBRIGATÓRIOS.');
            return false;
        }
    }

    // Assinaturas foram removidas do formulário; não são mais obrigatórias

    // Validação de cônjuge se casado
    if (document.getElementById('casado').checked) {
        const obrigatoriosConjuge = ['nome_conjuge', 'cpf_conjuge', 'telefone_conjuge'];
        for (let id of obrigatoriosConjuge) {
            const el = document.getElementById(id);
            if (el && !el.value.trim()) {
                e.preventDefault();
                showFlash('danger', 'PREENCHA TODOS OS DADOS OBRIGATÓRIOS DO CÔNJUGE.');
                return false;
            }
        }
        // Assinatura do cônjuge removida do formulário
    }
    // Assinaturas já estão salvas nos campos hidden
});
