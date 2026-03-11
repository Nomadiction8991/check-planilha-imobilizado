document.addEventListener('DOMContentLoaded', function () {
    inicializarConfirmacaoExclusao();
    inicializarSelecaoProdutos();
});

function inicializarConfirmacaoExclusao() {
    var confirmButton = document.getElementById('confirmDeleteButton');
    var deleteForm = document.getElementById('deleteForm');
    if (confirmButton && deleteForm) {
        confirmButton.addEventListener('click', function () {
            deleteForm.submit();
        });
    }
}

function inicializarSelecaoProdutos() {
    var checkboxes = document.querySelectorAll('.PRODUTO-checkbox');
    var deleteButtonContainer = document.getElementById('deleteButtonContainer');
    var selectedProductsDiv = document.getElementById('selectedProducts');
    var countSelected = document.getElementById('countSelected');

    function atualizarContagem() {
        var checados = document.querySelectorAll('.PRODUTO-checkbox:checked').length;
        if (countSelected) countSelected.textContent = checados;

        if (deleteButtonContainer) {
            deleteButtonContainer.style.display = checados > 0 ? 'block' : 'none';
        }

        if (selectedProductsDiv) {
            selectedProductsDiv.innerHTML = '';
            document.querySelectorAll('.PRODUTO-checkbox:checked').forEach(function (checkbox) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids_PRODUTOS[]';
                input.value = checkbox.value;
                selectedProductsDiv.appendChild(input);
            });
        }
    }

    checkboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', atualizarContagem);
    });
}
