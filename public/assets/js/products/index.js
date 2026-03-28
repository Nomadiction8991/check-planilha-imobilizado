document.addEventListener('DOMContentLoaded', function() {
    const confirmButton = document.getElementById('confirmDeleteButton');
    const openModalButton = document.getElementById('openDeleteModalButton');
    const cancelModalButton = document.getElementById('cancelDeleteModalButton');
    const confirmDeleteModal = document.getElementById('confirmDeleteModal');
    const deleteForm = document.getElementById('deleteForm');

    if (openModalButton && confirmDeleteModal) {
        openModalButton.addEventListener('click', function() {
            confirmDeleteModal.style.display = 'flex';
        });
    }

    if (cancelModalButton && confirmDeleteModal) {
        cancelModalButton.addEventListener('click', function() {
            confirmDeleteModal.style.display = 'none';
        });
    }

    if (confirmDeleteModal) {
        confirmDeleteModal.addEventListener('click', function(event) {
            if (event.target === confirmDeleteModal) {
                confirmDeleteModal.style.display = 'none';
            }
        });
    }

    if (confirmButton && deleteForm && confirmDeleteModal) {
        confirmButton.addEventListener('click', function() {
            confirmDeleteModal.style.display = 'none';
            deleteForm.submit();
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.PRODUTO-checkbox');
    const deleteButtonContainer = document.getElementById('deleteButtonContainer');
    const selectedProductsDiv = document.getElementById('selectedProducts');
    const countSelected = document.getElementById('countSelected');
    const deleteForm = document.getElementById('deleteForm');

    function atualizarContagem() {
        const checados = document.querySelectorAll('.PRODUTO-checkbox:checked').length;
        countSelected.textContent = checados;

        // Mostrar/ocultar container de exclusão
        if (checados > 0) {
            deleteButtonContainer.style.display = 'block';
        } else {
            deleteButtonContainer.style.display = 'none';
        }

        // ATUALIZAR inputs ocultos com IDs selecionados
        selectedProductsDiv.innerHTML = '';
        document.querySelectorAll('.PRODUTO-checkbox:checked').forEach(checkbox => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids_PRODUTOS[]';
            input.value = checkbox.value;
            selectedProductsDiv.appendChild(input);
        });
    }

    // Adicionar listener em cada checkbox
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', atualizarContagem);
    });
});
