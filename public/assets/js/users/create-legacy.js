// ========== MÁSCARAS COM INPUTMASK ==========
$(document).ready(function() {
    // Máscara CPF: 000.000.000-00
    Inputmask('999.999.999-99').mask('#cpf');
    Inputmask('999.999.999-99').mask('#cpf_conjuge');

    // Máscara TELEFONE: (00) 00000-0000 ou (00) 0000-0000
    Inputmask(['(99) 99999-9999', '(99) 9999-9999']).mask('#telefone');
    Inputmask(['(99) 99999-9999', '(99) 9999-9999']).mask('#telefone_conjuge');

    // Máscara CEP: 00000-000
    Inputmask('99999-999').mask('#cep');

    // Máscara RG: dígitos com traço antes do último (1 a 8 dígitos + '-' + 1 dígito ou X)
    // Removemos máscara de RG para usar formatação dinâmica

    // Toggle RG igual ao CPF
    // ======= RG Dinâmico =======
    function formatRgDigits(raw) {
        const digits = raw.replace(/\D/g, '');
        if (digits.length <= 1) return digits; // 1 dígito sem hífen
        return digits.slice(0, -1) + '-' + digits.slice(-1);
    }
    const rgInput = document.getElementById('rg');
    rgInput.addEventListener('input', function() {
        if (document.getElementById('rg_igual_cpf').checked) return; // quando igual CPF NÃO formata manual
        const pos = this.selectionStart;
        const raw = this.value;
        const formatted = formatRgDigits(raw);
        this.value = formatted;
    });

    function aplicarRgIgualCpf(aplicar) {
        if (aplicar) {
            // Aplica máscara de CPF ao RG e copia valor mascarado
            Inputmask('999.999.999-99').mask('#rg');
            const cpfMasked = document.getElementById('cpf').value;
            rgInput.value = cpfMasked;
            rgInput.setAttribute('disabled', 'disabled');
        } else {
            // Remove máscara e limpa para voltar à formatação dinâmica
            rgInput.removeAttribute('disabled');
            Inputmask.remove('#rg');
            rgInput.value = '';
        }
    }
    document.getElementById('rg_igual_cpf').addEventListener('change', function() {
        aplicarRgIgualCpf(this.checked);
    });
    document.getElementById('cpf').addEventListener('input', function() {
        if (document.getElementById('rg_igual_cpf').checked) aplicarRgIgualCpf(true);
    });

    // ======= RG Cônjuge Dinâmico =======
    const rgConjInput = document.getElementById('rg_conjuge');
    rgConjInput.addEventListener('input', function() {
        if (document.getElementById('rg_conjuge_igual_cpf').checked) return;
        const formatted = formatRgDigits(this.value);
        this.value = formatted;
    });

    function aplicarRgConjugeIgualCpf(aplicar) {
        if (aplicar) {
            Inputmask('999.999.999-99').mask('#rg_conjuge');
            const cpfMasked = document.getElementById('cpf_conjuge').value;
            rgConjInput.value = cpfMasked;
            rgConjInput.setAttribute('disabled', 'disabled');
        } else {
            rgConjInput.removeAttribute('disabled');
            Inputmask.remove('#rg_conjuge');
            rgConjInput.value = '';
        }
    }
    document.getElementById('rg_conjuge_igual_cpf').addEventListener('change', function() {
        aplicarRgConjugeIgualCpf(this.checked);
    });
    document.getElementById('cpf_conjuge').addEventListener('input', function() {
        if (document.getElementById('rg_conjuge_igual_cpf').checked) aplicarRgConjugeIgualCpf(true);
    });

    // Toggle cônjuge (mais robusto)
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

            // add/remove asterisks on labels
            const labels = {
                nome_conjuge: 'NOME COMPLETO DO CÔNJUGE',
                cpf_conjuge: 'CPF DO CÔNJUGE',
                telefone_conjuge: 'TELEFONE DO CÔNJUGE'
            };
            for (const id in labels) {
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
        };
        casadoCb.addEventListener('change', setVisibility);
        // inicializa baseado no estado atual
        setVisibility();
    })();
});

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
                showFlash('danger', 'CEP NÃO encontrado!');
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
            showFlash('danger', 'Erro ao buscar CEP. Tente novamente.');
            document.getElementById('logradouro').value = '';
        });
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

    // Validar senhas
    if (senha !== confirmar) {
        e.preventDefault();
        showFlash('danger', 'AS SENHAS NÃO CONFEREM!');
        return false;
    }

    // Campos endereço obrigatórios
    const enderecoObrigatorios = ['cep', 'logradouro', 'numero', 'bairro', 'cidade', 'estado'];
    for (let id of enderecoObrigatorios) {
        const el = document.getElementById(id);
        if (!el.value.trim()) {
            e.preventDefault();
            showFlash('danger', 'TODOS OS CAMPOS DE ENDEREÇO SÃO OBRIGATÓRIOS.');
            return false;
        }
    }

    if (document.getElementById('casado').checked) {
        const obrigatoriosConjuge = ['nome_conjuge', 'cpf_conjuge', 'telefone_conjuge'];
        for (let id of obrigatoriosConjuge) {
            const el = document.getElementById(id);
            if (!el.value.trim()) {
                e.preventDefault();
                showFlash('danger', 'PREENCHA TODOS OS DADOS OBRIGATÓRIOS DO CÔNJUGE.');
                return false;
            }
        }
    }
});
