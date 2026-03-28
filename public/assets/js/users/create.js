$(document).ready(function() {
    // Máscaras
    Inputmask('999.999.999-99').mask('#cpf, #cpf_conjuge');
    Inputmask('99.999.999-9').mask('#rg, #rg_conjuge');
    Inputmask('(99) 99999-9999').mask('#telefone, #telefone_conjuge');
    Inputmask('99999-999').mask('#cep');

    // Mostrar/ocultar dados do cônjuge
    $('#casado').on('change', function() {
        $('#cardConjuge').toggle(this.checked);

        // Tornar campos obrigatórios se casado
        if (this.checked) {
            $('#nome_conjuge, #cpf_conjuge, #rg_conjuge, #telefone_conjuge')
                .attr('required', true);
        } else {
            $('#nome_conjuge, #cpf_conjuge, #rg_conjuge, #telefone_conjuge')
                .removeAttr('required');
        }
    });

    // RG igual ao CPF
    $('#rg_igual_cpf').on('change', function() {
        if (this.checked) {
            const cpf = $('#cpf').val().replace(/\D/g, '');
            $('#rg').val(cpf).prop('readonly', true);
        } else {
            $('#rg').val('').prop('readonly', false);
        }
    });

    $('#rg_conjuge_igual_cpf').on('change', function() {
        if (this.checked) {
            const cpf = $('#cpf_conjuge').val().replace(/\D/g, '');
            $('#rg_conjuge').val(cpf).prop('readonly', true);
        } else {
            $('#rg_conjuge').val('').prop('readonly', false);
        }
    });

    // Buscar CEP
    $('#cep').on('blur', function() {
        const cep = $(this).val().replace(/\D/g, '');

        if (cep.length === 8) {
            $.getJSON(`https://viacep.com.br/ws/${cep}/json/`, function(data) {
                if (!data.erro) {
                    $('#logradouro').val(data.logradouro.toUpperCase());
                    $('#bairro').val(data.bairro.toUpperCase());
                    $('#cidade').val(data.localidade.toUpperCase());
                    $('#estado').val(data.uf);
                    $('#numero').focus();
                } else {
                    alert('CEP NÃO ENCONTRADO!');
                }
            }).fail(function() {
                alert('ERRO AO BUSCAR CEP!');
            });
        }
    });

    // Validar senha
    $('#formUsuario').on('submit', function(e) {
        const senha = $('#senha').val();
        const confirmar = $('#confirmar_senha').val();

        if (senha !== confirmar) {
            e.preventDefault();
            alert('AS SENHAS NÃO COINCIDEM!');
            return false;
        }

        if (senha.length < 6) {
            e.preventDefault();
            alert('A SENHA DEVE TER NO MÍNIMO 6 CARACTERES!');
            return false;
        }
    });
});
