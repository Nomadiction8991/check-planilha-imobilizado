(() => {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');

    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            } else {
                // Desabilita botão e mostra loading
                const btn = document.getElementById('btn-enviar');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>ANALISANDO...';
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
