// Mapeamento de tipos de bens e suas opções de bem
const tiposBensOpcoes = window._tiposBensOpcoes || {};

document.addEventListener('DOMContentLoaded', function() {
    const selectTipoBEM = document.getElementById('novo_tipo_bem_id');
    const selectBEM = document.getElementById('novo_bem');

    // Função para atualizar opções de BEM baseado no TIPO DE BEM selecionado
    function atualizarOpcoesBEM() {
        const tipoBEMId = selectTipoBEM.value;

        if (!tipoBEMId) {
            // Desabilitar e limpar
            selectBEM.disabled = true;
            selectBEM.innerHTML = '<option value="">-- ESCOLHA O TIPO DE BEM ACIMA --</option>';
            return;
        }

        const opcoes = tiposBensOpcoes[tipoBEMId]?.opcoes || [];

        if (opcoes.length > 1) {
            // Tem múltiplas opções separadas por /
            selectBEM.disabled = false;
            selectBEM.innerHTML = '<option value="">-- SELECIONE --</option>';
            opcoes.forEach(opcao => {
                const opt = document.createElement('option');
                opt.value = opcao.toUpperCase();
                opt.textContent = opcao.toUpperCase();
                selectBEM.appendChild(opt);
            });
        } else if (opcoes.length === 1) {
            // Apenas uma opção, preencher automaticamente
            selectBEM.disabled = false;
            selectBEM.innerHTML = '';
            const opt = document.createElement('option');
            opt.value = opcoes[0].toUpperCase();
            opt.textContent = opcoes[0].toUpperCase();
            opt.selected = true;
            selectBEM.appendChild(opt);
        } else {
            // Sem opções, campo livre
            selectBEM.disabled = true;
            selectBEM.innerHTML = '<option value="">-- NÃO APLICÁVEL --</option>';
        }
    }

    // Listener para mudança de TIPO DE BEM
    selectTipoBEM.addEventListener('change', atualizarOpcoesBEM);

    // Inicializar estado
    atualizarOpcoesBEM();

    // Converter inputs para uppercase automaticamente
    document.querySelectorAll('.text-uppercase-input').forEach(input => {
        input.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    });

    // Pré-preencher BEM usando o valor já processado pelo controller (editado ou original)
    const bemPrefill = window._novoBem || '';
    if (bemPrefill) {
        if (selectTipoBEM.value) {
            atualizarOpcoesBEM();
            for (const opt of selectBEM.options) {
                if (opt.value === bemPrefill) {
                    opt.selected = true;
                    break;
                }
            }
        } else {
            selectBEM.innerHTML = '<option value="' + bemPrefill + '" selected>' + bemPrefill + '</option>';
            selectBEM.disabled = true;
        }
    }
});
