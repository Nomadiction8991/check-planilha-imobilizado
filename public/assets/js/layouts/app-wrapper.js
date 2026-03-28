/**
 * app-wrapper.js – Scripts do layout wrapper (app_wrapper.php)
 */

// Função para voltar (igual ao botão do navegador)
function goBack() {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = '/churches';
    }
}

// Bloqueio de zoom global (pinch/double-tap) fora do viewer do relatório
(function () {
    var isViewerOpen = function () {
        var ov = document.getElementById('viewerOverlay');
        return !!(ov && !ov.hasAttribute('hidden'));
    };

    // Evita pinch-zoom (2+ dedos) fora do viewer
    document.addEventListener('touchstart', function (e) {
        if (isViewerOpen()) return;
        if (e.touches && e.touches.length > 1) {
            e.preventDefault();
        }
    }, { passive: false });

    // Evita double-tap zoom fora do viewer
    var lastTouchEnd = 0;
    document.addEventListener('touchend', function (e) {
        if (isViewerOpen()) return;
        var now = Date.now();
        if (now - lastTouchEnd <= 300) {
            e.preventDefault();
        }
        lastTouchEnd = now;
    }, { passive: false });

    // Alguns navegadores disparam gesturestart (iOS antigos)
    document.addEventListener('gesturestart', function (e) {
        if (isViewerOpen()) return;
        e.preventDefault();
    });

    // Melhora em navegadores que suportam touch-action
    document.body.style.touchAction = 'manipulation';
})();

// Garantir que modais fiquem dentro do wrapper mobile
document.addEventListener('show.bs.modal', function (event) {
    var appWrapper = document.querySelector('.mobile-wrapper');
    if (!appWrapper) return;
    var modal = event.target;
    if (modal && modal.parentElement !== appWrapper) {
        appWrapper.appendChild(modal);
    }

    // Mover backdrop para dentro do wrapper
    setTimeout(function () {
        var backdrop = document.querySelector('.modal-backdrop');
        if (backdrop && backdrop.parentElement !== appWrapper) {
            appWrapper.appendChild(backdrop);
        }
    }, 10);
});

// PWA Service Worker Registration
(function () {
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            var config = window._swConfig || {};
            var swPath = config.swPath || '/sw.js';
            navigator.serviceWorker.register(swPath)
                .then(function (registration) {
                    console.log('Service Worker registrado com sucesso:', registration.scope);
                    console.log('Ambiente:', config.ambiente || 'prod');
                })
                .catch(function (err) {
                    console.error('Falha ao registrar Service Worker:', err);
                });
        });
    }
})();

// Auto-dismiss alerts
(function () {
    var AUTO_MS = 3000;
    var FADE_MS = 1000;

    function processAlert(el) {
        if (!el || el.dataset._autoDismissProcessed) return;
        el.dataset._autoDismissProcessed = '1';

        // Remove botão fechar (X)
        var closeBtn = el.querySelector('.btn-close');
        if (closeBtn) closeBtn.remove();

        el.classList.add('fade');
        el.style.transition = 'opacity ' + FADE_MS + 'ms ease';

        if (!el.classList.contains('show')) el.classList.add('show');

        setTimeout(function () {
            el.classList.remove('show');
            setTimeout(function () {
                try { el.remove(); } catch (e) {}
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
