// Helper: feedback Bootstrap inline
function showFlash(type, message) {
    var el = document.createElement('div');
    el.className = 'alert alert-' + type + ' alert-dismissible fade show';
    el.setAttribute('role', 'alert');
    el.setAttribute('aria-live', 'polite');
    var iconName = (type === 'success') ? 'check-circle' : 'exclamation-triangle';
    var iconEl = document.createElement('i');
    iconEl.className = 'bi bi-' + iconName + ' me-2';
    var msgSpan = document.createElement('span');
    msgSpan.textContent = message;
    var closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'btn-close';
    closeBtn.setAttribute('data-bs-dismiss', 'alert');
    el.append(iconEl, msgSpan, closeBtn);
    var container = document.querySelector('.app-content') || document.body;
    container.insertBefore(el, container.firstChild);
}

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
        showFlash('warning', 'Selecione pelo menos um PRODUTO para assinar');
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
        showFlash('warning', 'Selecione pelo menos um PRODUTO para remover a assinatura');
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
                showFlash('success', data.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                showFlash('danger', 'Erro: ' + data.message);
            }
        })
        .catch(() => {
            showFlash('danger', 'Erro ao processar solicitação');
        });
}
