document.addEventListener('DOMContentLoaded', function() {
    const modalCadastro = new bootstrap.Modal(document.getElementById('modalCadastroIncompleto'));
    const btnCompletar = document.getElementById('btnCompletarCadastro');

    // Handler para botões "visualizar planilha"
    document.querySelectorAll('.btn-view-planilha').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();

            const cadastroCompleto = this.dataset.cadastroOk === '1';
            const editUrl = this.dataset.editUrl;
            const viewUrl = this.dataset.viewUrl;

            if (!cadastroCompleto) {
                // Mostrar modal de cadastro incompleto
                btnCompletar.href = editUrl;
                modalCadastro.show();
            } else {
                // Redirecionar para visualização da planilha
                window.location.href = viewUrl;
            }
        });
    });
});
