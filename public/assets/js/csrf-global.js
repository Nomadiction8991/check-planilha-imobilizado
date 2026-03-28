/**
 * csrf-global.js — Proteção CSRF automática para TODOS formulários e requisições AJAX.
 *
 * 1) Injeta campo hidden _csrf_token em todo <form method="POST"> da página.
 * 2) Intercepta window.fetch() para adicionar header X-CSRF-Token em toda requisição POST/PUT/PATCH/DELETE.
 *
 * Basta incluir este script no layout principal. Nenhuma view individual precisa se preocupar com CSRF.
 */
(function () {
    'use strict';

    /** Lê o token da meta tag <meta name="csrf-token" content="..."> */
    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    /* ── 1) Injetar hidden field em todos os formulários POST existentes ─ */
    function injectCsrfInForms() {
        var token = getCsrfToken();
        if (!token) return;

        document.querySelectorAll('form').forEach(function (form) {
            var method = (form.getAttribute('method') || 'GET').toUpperCase();
            if (method !== 'POST') return;

            // Não duplicar se já existe
            if (form.querySelector('input[name="_csrf_token"]')) return;

            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = '_csrf_token';
            input.value = token;
            form.appendChild(input);
        });
    }

    // Executa ao carregar a página
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', injectCsrfInForms);
    } else {
        injectCsrfInForms();
    }

    // Observa formulários adicionados dinamicamente (modais, AJAX, etc.)
    if (typeof MutationObserver !== 'undefined') {
        new MutationObserver(function (mutations) {
            for (var i = 0; i < mutations.length; i++) {
                if (mutations[i].addedNodes.length > 0) {
                    injectCsrfInForms();
                    break;
                }
            }
        }).observe(document.body || document.documentElement, { childList: true, subtree: true });
    }

    /* ── 2) Interceptar fetch() para adicionar header CSRF automaticamente ── */
    var originalFetch = window.fetch;
    window.fetch = function (input, init) {
        init = init || {};
        var method = (init.method || 'GET').toUpperCase();

        // Métodos que alteram estado precisam de CSRF
        if (['POST', 'PUT', 'PATCH', 'DELETE'].indexOf(method) !== -1) {
            var token = getCsrfToken();
            if (token) {
                // Garantir que headers existe
                if (init.headers instanceof Headers) {
                    if (!init.headers.has('X-CSRF-Token')) {
                        init.headers.set('X-CSRF-Token', token);
                    }
                } else if (typeof init.headers === 'object' && init.headers !== null) {
                    if (!init.headers['X-CSRF-Token'] && !init.headers['X-Csrf-Token']) {
                        init.headers['X-CSRF-Token'] = token;
                    }
                } else {
                    init.headers = { 'X-CSRF-Token': token };
                }
            }
        }

        return originalFetch.call(this, input, init);
    };

    /* ── 3) Interceptar XMLHttpRequest para compatibilidade ── */
    var originalOpen = XMLHttpRequest.prototype.open;
    var originalSend = XMLHttpRequest.prototype.send;

    XMLHttpRequest.prototype.open = function (method) {
        this._csrfMethod = (method || 'GET').toUpperCase();
        return originalOpen.apply(this, arguments);
    };

    XMLHttpRequest.prototype.send = function () {
        if (['POST', 'PUT', 'PATCH', 'DELETE'].indexOf(this._csrfMethod) !== -1) {
            var token = getCsrfToken();
            if (token) {
                try {
                    this.setRequestHeader('X-CSRF-Token', token);
                } catch (e) { /* header já definido */ }
            }
        }
        return originalSend.apply(this, arguments);
    };
})();
