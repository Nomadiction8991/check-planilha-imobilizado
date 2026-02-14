function selecionarTodos() {
    document.querySelectorAll('.PRODUTO-checkbox').forEach(cb => {
        cb.checked = true;
        cb.closest('.PRODUTO-card').classList.add('selecionado');
    });
}

function desmarcarTodos() {
    document.querySelectorAll('.PRODUTO-checkbox').forEach(cb => {
        cb.checked = false;
        cb.closest('.PRODUTO-card').classList.remove('selecionado');
    });
}

// ATUALIZAR visual ao selecionar
document.querySelectorAll('.PRODUTO-checkbox').forEach(cb => {
    cb.addEventListener('change', function() {
        if (this.checked) {
            this.closest('.PRODUTO-card').classList.add('selecionado');
        } else {
            this.closest('.PRODUTO-card').classList.remove('selecionado');
        }
    });
});

function assinarPRODUTOS() {
    const selecionados = Array.from(document.querySelectorAll('.PRODUTO-checkbox:checked'))
        .map(cb => cb.value);

    if (selecionados.length === 0) {
        alert('Selecione pelo menos um PRODUTO para assinar');
        return;
    }

    if (!confirm(`Deseja assinar ${selecionados.length} PRODUTO(s)?`)) {
        return;
    }

    executarAcao('assinar', selecionados);
}

function desassinarPRODUTOS() {
    const selecionados = Array.from(document.querySelectorAll('.PRODUTO-checkbox:checked'))
        .map(cb => cb.value);

    if (selecionados.length === 0) {
        alert('Selecione pelo menos um PRODUTO para remover a assinatura');
        return;
    }

    if (!confirm(`Deseja remover sua assinatura de ${selecionados.length} PRODUTO(s)?`)) {
        return;
    }

    executarAcao('desassinar', selecionados);
}

function executarAcao(acao, PRODUTOS) {
    const formData = new FormData();
    formData.append('acao', acao);
    PRODUTOS.forEach(id => formData.append('PRODUTOS[]', id));

    fetch('/products/sign', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Erro: ' + data.message);
            }
        })
        .catch(error => {
            alert('Erro ao processar solicitação');
            console.error(error);
        });
}
