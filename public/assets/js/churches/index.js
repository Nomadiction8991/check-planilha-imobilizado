document.addEventListener('DOMContentLoaded', function () {

    // --- Modal: Excluir todos os produtos da comum ---
    const elModalDelete = document.getElementById('modalDeleteProdutos');
    if (elModalDelete) {
        const modalDelete = new bootstrap.Modal(elModalDelete);
        const nomeComumEl   = document.getElementById('modalDeleteNomeComum');
        const deleteComumIdEl = document.getElementById('deleteComumId');
        const countMsgEl    = document.getElementById('modalDeleteCount');
        const btnConfirm    = document.getElementById('btnConfirmDeleteProdutos');

        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-delete-products');
            if (!btn) return;

            const comumId   = btn.dataset.comumId;
            const comumNome = btn.dataset.comumNome;

            // Reseta estado visual
            nomeComumEl.textContent  = comumNome;
            deleteComumIdEl.value    = comumId;
            countMsgEl.textContent   = 'Carregando...';
            btnConfirm.disabled      = true;

            modalDelete.show();

            // Busca a quantidade de produtos via AJAX
            fetch('/churches/products-count?comum_id=' + encodeURIComponent(comumId))
                .then(r => r.json())
                .then(data => {
                    const n = data.count || 0;
                    if (n === 0) {
                        countMsgEl.innerHTML = '<span class="text-muted">Nenhum produto cadastrado nesta comum.</span>';
                        btnConfirm.disabled = true;
                    } else {
                        countMsgEl.innerHTML =
                            'Serão excluídos <strong class="text-danger">' + n + ' produto(s)</strong>.';
                        btnConfirm.disabled = false;
                    }
                })
                .catch(() => {
                    countMsgEl.textContent = 'Não foi possível obter a contagem.';
                    btnConfirm.disabled = false; // permite mesmo assim
                });
        });
    }
});
