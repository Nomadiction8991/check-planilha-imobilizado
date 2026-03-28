/**
 * navigation-init.js - Inicialização completa de navegação
 * Suporta menu, footer, alerts e acessibilidade
 */

(function () {
    'use strict';

    // 1. Inicializar menu com toggle de submenus
    function initMenuToggles() {
        var menuToggles = document.querySelectorAll('.menu-toggle');
        if (menuToggles.length === 0) return;

        menuToggles.forEach(function (toggle) {
            // Estado inicial
            toggle.setAttribute('aria-expanded', 'false');

            toggle.addEventListener('click', function (e) {
                e.preventDefault();
                var expanded = this.getAttribute('aria-expanded') === 'true';
                var submenu = this.nextElementSibling;

                if (!submenu || !submenu.classList.contains('menu-submenu')) {
                    return;
                }

                var icon = this.querySelector('.toggle-icon');

                if (expanded) {
                    // Fechar
                    submenu.style.maxHeight = '0px';
                    if (icon) {
                        icon.style.transform = 'rotate(0deg)';
                    }
                    this.setAttribute('aria-expanded', 'false');
                } else {
                    // Abrir
                    var height = submenu.scrollHeight;
                    submenu.style.maxHeight = height + 'px';
                    if (icon) {
                        icon.style.transform = 'rotate(180deg)';
                    }
                    this.setAttribute('aria-expanded', 'true');
                }
            });

            // Keyboard support (Enter/Space)
            toggle.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.click();
                }
            });
        });
    }

    // 2. Fechar submenu ao navegar
    function initSubmenuNavigation() {
        var submenuLinks = document.querySelectorAll('.menu-submenu a');
        submenuLinks.forEach(function (link) {
            link.addEventListener('click', function () {
                var submenu = this.parentElement.parentElement;
                var toggle = submenu.previousElementSibling;

                if (toggle && toggle.classList.contains('menu-toggle')) {
                    submenu.style.maxHeight = '0px';
                    var icon = toggle.querySelector('.toggle-icon');
                    if (icon) {
                        icon.style.transform = 'rotate(0deg)';
                    }
                    toggle.setAttribute('aria-expanded', 'false');
                }
            });
        });
    }

    // 3. Inicializar footer buttons
    function initFooterButtons() {
        var goBackBtn = document.querySelector('[onclick*="goBack"]');
        if (goBackBtn) {
            goBackBtn.setAttribute('role', 'button');
            goBackBtn.setAttribute('tabindex', '0');
            goBackBtn.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    goBack();
                }
            });
        }
    }

    // 4. Auto-dismiss alerts
    function initAlertDismiss() {
        var AUTO_MS = 3000;
        var FADE_MS = 500;

        function processAlert(el) {
            if (!el || el.dataset._autoDismissProcessed) return;
            el.dataset._autoDismissProcessed = '1';

            // Adicionar listener ao botão de fechar
            var closeBtn = el.querySelector('button[aria-label="Fechar"]');
            if (closeBtn) {
                closeBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    el.style.opacity = '0';
                    setTimeout(function () {
                        el.remove();
                    }, FADE_MS);
                });
            }

            // Auto-dismiss
            setTimeout(function () {
                if (el.parentElement) {
                    el.style.opacity = '0';
                    el.style.transition = 'opacity ' + FADE_MS + 'ms ease';
                    setTimeout(function () {
                        if (el.parentElement) {
                            el.remove();
                        }
                    }, FADE_MS);
                }
            }, AUTO_MS);
        }

        // Processar alerts existentes
        document.querySelectorAll('[role="alert"]').forEach(processAlert);

        // Observer para novos alerts
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(function (node) {
                        if (node.nodeType === 1) { // Element node
                            if (node.getAttribute && node.getAttribute('role') === 'alert') {
                                processAlert(node);
                            } else if (node.querySelectorAll) {
                                node.querySelectorAll('[role="alert"]').forEach(processAlert);
                            }
                        }
                    });
                }
            });
        });

        observer.observe(document.body, { childList: true, subtree: true });
    }

    // 5. Global goBack function (compatível com footer)
    if (typeof window.goBack !== 'function') {
        window.goBack = function () {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = '/products/view';
            }
        };
    }

    // 6. Iniciar tudo quando DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initMenuToggles();
            initSubmenuNavigation();
            initFooterButtons();
            initAlertDismiss();
        });
    } else {
        // DOM já carregado
        initMenuToggles();
        initSubmenuNavigation();
        initFooterButtons();
        initAlertDismiss();
    }
})();
