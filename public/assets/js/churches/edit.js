document.addEventListener('DOMContentLoaded', function() {
    // Auto-uppercase para campos específicos
    const upperFields = document.querySelectorAll('.text-uppercase');
    upperFields.forEach(field => {
        field.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    });

    // Máscara de CNPJ
    const cnpjField = document.getElementById('cnpj');
    if (cnpjField) {
        cnpjField.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 14) {
                value = value.replace(/^(\d{2})(\d)/, '$1.$2');
                value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
                value = value.replace(/(\d{4})(\d)/, '$1-$2');
                e.target.value = value;
            }
        });
    }

    // Máscara de telefone
    const telefoneField = document.getElementById('telefone');
    if (telefoneField) {
        telefoneField.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                if (value.length <= 10) {
                    value = value.replace(/^(\d{2})(\d)/, '($1) $2');
                    value = value.replace(/(\d{4})(\d)/, '$1-$2');
                } else {
                    value = value.replace(/^(\d{2})(\d)/, '($1) $2');
                    value = value.replace(/(\d{5})(\d)/, '$1-$2');
                }
                e.target.value = value;
            }
        });
    }

    // Validação do formulário
    const form = document.getElementById('formEditarComum');
    if (form) {
        form.addEventListener('submit', function(e) {
            const codigo = document.getElementById('codigo').value.trim();
            const descricao = document.getElementById('descricao').value.trim();

            if (!codigo || !descricao) {
                e.preventDefault();
                alert('Código e Descrição são obrigatórios!');
                return false;
            }
        });
    }
});
