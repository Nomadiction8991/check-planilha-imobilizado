/**
 * header-mobile.js – Seletor de comum no header mobile
 */
document.addEventListener('DOMContentLoaded', function () {
    var selector = document.getElementById('comum-selector');
    if (!selector) return;

    selector.addEventListener('change', function () {
        var comumId = this.value;
        if (!comumId) return;

        fetch('/users/select-church', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
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
