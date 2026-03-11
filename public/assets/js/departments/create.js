// Validação do formulário de criação de dependência
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('formDependenciaCreate');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        var descricaoInput = document.getElementById('descricao');
        var descricao = descricaoInput ? descricaoInput.value.trim() : '';

        if (!descricao) {
            e.preventDefault();

            if (descricaoInput) {
                descricaoInput.classList.add('is-invalid');

                var feedback = descricaoInput.parentNode.querySelector('.invalid-feedback');
                if (!feedback) {
                    feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    descricaoInput.parentNode.appendChild(feedback);
                }
                feedback.textContent = 'A DESCRIÇÃO É OBRIGATÓRIA!';
                descricaoInput.focus();
            }
            return false;
        }
    });

    // Remover estado inválido ao digitar
    var descricaoInput = document.getElementById('descricao');
    if (descricaoInput) {
        descricaoInput.addEventListener('input', function () {
            this.classList.remove('is-invalid');
        });
    }
});
