/**
 * form-submit-lock.js
 * Desabilita o botão de submit durante o processamento do formulário,
 * exibindo um spinner de feedback. Ative em um formulário adicionando
 * o atributo data-submit-lock ao elemento <form>.
 */
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('form[data-submit-lock]').forEach(function(form) {
        form.addEventListener('submit', function() {
            const btn = form.querySelector('[type="submit"]');
            if (btn && !btn.disabled) {
                btn.disabled = true;
                btn.dataset.originalText = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processando...';
            }
        });
    });
});
