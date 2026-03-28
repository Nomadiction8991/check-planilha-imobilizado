function showFlash(type, message) {
    const el = document.createElement('div');
    el.className = 'alert alert-' + type + ' alert-dismissible fade show';
    el.setAttribute('role', 'alert');
    const icon = (type === 'success') ? 'check-circle' : 'exclamation-triangle';
    el.innerHTML = '<i class="bi bi-' + icon + ' me-2"></i><span></span><button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    el.querySelector('span').textContent = message;
    // Insert at top of main content area if available
    const container = document.querySelector('.app-content .container-fluid') || document.querySelector('.app-content') || document.body;
    container.insertBefore(el, container.firstChild);
}

function deletarDependencia(id) {
    // Open confirm modal and attach id to confirm button
    const modal = document.getElementById('confirmModalDependencia');
    if (!modal) {
        // fallback to prompt if modal not available
        if (!confirm('Tem certeza que deseja excluir esta dependência?')) return;
        performDelete(id);
        return;
    }
    const confirmBtn = modal.querySelector('.confirm-delete');
    modal.querySelector('.modal-body span').textContent = 'Deseja realmente excluir esta dependência?';
    confirmBtn.setAttribute('data-delete-id', id);
    // show modal using Bootstrap API if available
    if (typeof bootstrap !== 'undefined') {
        const bs = new bootstrap.Modal(modal);
        bs.show();
    } else {
        modal.style.display = 'block';
        modal.classList.add('show');
    }
}

function performDelete(id) {
    fetch('/departments/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + id
        })
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    showFlash('success', data.message);
                    // Give user a moment to see the flash before reloading
                    setTimeout(function() {
                        location.reload();
                    }, 900);
                } else {
                    showFlash('danger', data.message);
                }
            } catch (e) {
                console.error('Invalid JSON response:', text);
                showFlash('danger', 'Erro na requisição: resposta inválida do servidor. Verifique o console para detalhes.');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            showFlash('danger', 'Erro na requisição: ' + String(error));
        });
}

// Bind modal confirm button
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('confirmModalDependencia');
        if (!modal) return;
        modal.querySelector('.confirm-delete').addEventListener('click', function(e) {
            const id = this.getAttribute('data-delete-id');
            // hide modal
            if (typeof bootstrap !== 'undefined') {
                bootstrap.Modal.getInstance(modal)?.hide();
            } else {
                modal.classList.remove('show');
                modal.style.display = 'none';
            }
            if (id) performDelete(id);
        });
    });
})();
