function showFlash(type, message) {
    const el = document.createElement('div');
    el.className = 'alert alert-' + type + ' alert-dismissible fade show';
    el.setAttribute('role', 'alert');
    const icon = (type === 'success') ? 'check-circle' : 'exclamation-triangle';
    el.innerHTML = '<i class="bi bi-' + icon + ' me-2"></i><span></span><button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    el.querySelector('span').textContent = message;
    const container = document.querySelector('.app-content .container-fluid') || document.querySelector('.app-content') || document.body;
    container.insertBefore(el, container.firstChild);
}

document.addEventListener('DOMContentLoaded', function() {
    window.excluirUsuario = function(id, nome) {
        if (!confirm('TEM CERTEZA QUE DESEJA EXCLUIR O USUÁRIO "' + nome + '"?')) {
            return;
        }

        fetch('/users/delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + id
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showFlash('success', data.message);
                    setTimeout(function() {
                        location.reload();
                    }, 900);
                } else {
                    showFlash('danger', data.message);
                }
            })
            .catch(error => {
                showFlash('danger', 'ERRO AO EXCLUIR USUÁRIO');
                console.error(error);
            });
    }
});
