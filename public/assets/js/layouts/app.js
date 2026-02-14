/**
 * app.js – Scripts do layout principal (app.php)
 */

// PWA Service Worker Registration
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
        navigator.serviceWorker.register('/sw.js')
            .then(function (reg) {
                console.log('Service Worker registrado:', reg.scope);
            })
            .catch(function (err) {
                console.error('Erro ao registrar Service Worker:', err);
            });
    });
}

// Função para voltar (igual ao botão do navegador)
function goBack() {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = '/spreadsheets/view';
    }
}

// Auto-dismiss alerts
(function () {
    var AUTO_MS = 3000;
    var FADE_MS = 1000;

    function processAlert(el) {
        if (!el || el.dataset._autoDismissProcessed) return;
        el.dataset._autoDismissProcessed = '1';

        // Remove botão fechar
        var closeBtn = el.querySelector('.btn-close');
        if (closeBtn) closeBtn.remove();

        el.classList.add('fade');
        el.style.transition = 'opacity ' + FADE_MS + 'ms ease';

        if (!el.classList.contains('show')) el.classList.add('show');

        setTimeout(function () {
            el.classList.remove('show');
            setTimeout(function () {
                el.remove();
            }, FADE_MS + 20);
        }, AUTO_MS);
    }

    document.querySelectorAll('.alert').forEach(processAlert);

    var mo = new MutationObserver(function (muts) {
        for (var i = 0; i < muts.length; i++) {
            var m = muts[i];
            for (var j = 0; j < m.addedNodes.length; j++) {
                var node = m.addedNodes[j];
                if (!(node instanceof HTMLElement)) continue;
                if (node.classList && node.classList.contains('alert')) processAlert(node);
                if (node.querySelectorAll) node.querySelectorAll('.alert').forEach(processAlert);
            }
        }
    });
    mo.observe(document.body, { childList: true, subtree: true });
})();

// Garantir que modais fiquem dentro do wrapper mobile
document.addEventListener('show.bs.modal', function (event) {
    var appWrapper = document.querySelector('.mobile-wrapper');
    if (!appWrapper) return;
    var modal = event.target;
    if (modal && modal.parentElement !== appWrapper) {
        appWrapper.appendChild(modal);
    }
});
