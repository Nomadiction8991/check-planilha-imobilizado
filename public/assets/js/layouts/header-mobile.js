/**
 * header-mobile.js – Seletor de comum no header mobile
 */
document.addEventListener('DOMContentLoaded', function () {
    var selector = document.getElementById('comum-selector');
    if (!selector) return;

    selector.addEventListener('change', function () {
        var comumId = this.value;
        if (!comumId) return;

        // Obter token CSRF da meta tag
        var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        fetch('/users/select-church', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({ comum_id: comumId })
        })
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Erro ao selecionar comum: ' + (data.message || 'Erro desconhecido'));
            }
        })
        .catch(function (error) {
            console.error('Erro:', error);
            alert('Erro ao selecionar comum');
        });
    });
});
