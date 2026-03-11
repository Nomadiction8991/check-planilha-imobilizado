document.addEventListener('DOMContentLoaded', function () {
    // Auto-uppercase para campos específicos
    var upperFields = document.querySelectorAll('.text-uppercase');
    upperFields.forEach(function (field) {
        field.addEventListener('input', function () {
            this.value = this.value.toUpperCase();
        });
    });

    // Máscara de CNPJ via Inputmask (carregado antes deste script)
    var cnpjField = document.getElementById('cnpj');
    if (cnpjField) {
        if (typeof Inputmask !== 'undefined') {
            Inputmask({
                mask: '99.999.999/9999-99',
                clearIncomplete: true
            }).mask(cnpjField);
        } else {
            // Fallback: máscara manual caso a biblioteca não esteja disponível
            cnpjField.addEventListener('input', function (e) {
                var value = e.target.value.replace(/\D/g, '');
                if (value.length <= 14) {
                    value = value.replace(/^(\d{2})(\d)/, '$1.$2');
                    value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                    value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
                    value = value.replace(/(\d{4})(\d)/, '$1-$2');
                    e.target.value = value;
                }
            });
        }
    }

    // Validação do formulário com feedback Bootstrap
    var form = document.getElementById('formEditarComum');
    if (form) {
        form.addEventListener('submit', function (e) {
            var codigoInput = document.getElementById('codigo');
            var descricaoInput = document.getElementById('descricao');
            var valido = true;

            [codigoInput, descricaoInput].forEach(function (input) {
                if (!input) return;
                if (!input.value.trim()) {
                    input.classList.add('is-invalid');
                    valido = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            if (!valido) {
                e.preventDefault();
                return false;
            }
        });

        // Remover estado inválido ao digitar
        ['codigo', 'descricao'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', function () {
                    this.classList.remove('is-invalid');
                });
            }
        });
    }
});
