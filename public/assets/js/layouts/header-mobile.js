/**
 * header-mobile.js – Seletor de comum no header mobile
 */

function mostrarErroHeader(mensagem) {
    var container = document.querySelector('.app-content') || document.body;
    var el = document.createElement('div');
    el.className = 'alert alert-danger alert-dismissible fade show';
    el.setAttribute('role', 'alert');
    el.setAttribute('aria-live', 'polite');
    el.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i><span></span>'
        + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    el.querySelector('span').textContent = mensagem;
    container.insertBefore(el, container.firstChild);
}

document.addEventListener('DOMContentLoaded', function () {
    var selector = document.getElementById('comum-selector');
    if (!selector) return;

    selector.addEventListener('change', function () {
        var comumId = this.value;
        if (!comumId) return;

        // Desabilita o select durante a troca para evitar dupla chamada
        selector.disabled = true;

        // CSRF token é adicionado automaticamente pelo csrf-global.js
        fetch('/users/select-church', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ comum_id: parseInt(comumId, 10) })
        })
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (data.success) {
                // Navega para a URL atual atualizando ?comum_id= para o novo valor.
                // Isso garante que PlanilhaController::visualizar() persista o comune_id
                // correto na sessão, evitando que uma URL antiga com comum_id diferente
                // reverta a troca logo após o reload.
                var url = new URL(window.location.href);
                url.searchParams.set('comum_id', comumId);
                window.location.href = url.toString();
            } else {
                selector.disabled = false;
                console.error('Erro ao selecionar comum:', data);
                mostrarErroHeader('Erro ao selecionar comum: ' + (data.message || 'Erro desconhecido'));
            }
        })
        .catch(function (error) {
            selector.disabled = false;
            console.error('Erro ao selecionar comum:', error);
            mostrarErroHeader('Erro de comunicação ao trocar de igreja.');
        });
    });
});
