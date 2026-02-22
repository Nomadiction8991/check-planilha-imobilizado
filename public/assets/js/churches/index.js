document.addEventListener('DOMContentLoaded', function() {
    // --- Modal: Cadastro Incompleto ---
    const elModalCadastro = document.getElementById('modalCadastroIncompleto');
    if (elModalCadastro) {
        const modalCadastro = new bootstrap.Modal(elModalCadastro);
        const btnCompletar = document.getElementById('btnCompletarCadastro');

        document.querySelectorAll('.btn-view-planilha').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const cadastroCompleto = this.dataset.cadastroOk === '1';
                const editUrl = this.dataset.editUrl;
                const viewUrl = this.dataset.viewUrl;

                if (!cadastroCompleto) {
                    btnCompletar.href = editUrl;
                    modalCadastro.show();
                } else {
                    window.location.href = viewUrl;
                }
            });
        });
    }

    // --- Modal: Excluir todos os produtos da comum ---
    const elModalDelete = document.getElementById('modalDeleteProdutos');
    if (elModalDelete) {
        const modalDelete = new bootstrap.Modal(elModalDelete);
        const nomeComumEl = document.getElementById('modalDeleteNomeComum');
        const deleteComumIdEl = document.getElementById('deleteComumId');

        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-delete-products');
            if (!btn) return;

            nomeComumEl.textContent = btn.dataset.comumNome;
            deleteComumIdEl.value = btn.dataset.comumId;
            modalDelete.show();
        });
    }
});
