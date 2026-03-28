/**
 * menu.js – Comportamento de toggle para submenus com animação suave
 */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.menu-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var expanded = this.getAttribute('aria-expanded') === 'true';
            var submenu = this.nextElementSibling; // .menu-submenu
            var icon = this.querySelector('.toggle-icon');

            if (submenu && submenu.classList.contains('menu-submenu')) {
                if (expanded) {
                    // Fechar
                    submenu.style.maxHeight = '0';
                    if (icon) icon.style.transform = 'rotate(0deg)';
                    this.setAttribute('aria-expanded', 'false');
                } else {
                    // Abrir
                    submenu.style.maxHeight = submenu.scrollHeight + 'px';
                    if (icon) icon.style.transform = 'rotate(180deg)';
                    this.setAttribute('aria-expanded', 'true');
                }
            }
        });
    });

    // Fechar submenu ao clicar em um item (navegação)
    document.querySelectorAll('.menu-submenu a').forEach(function (link) {
        link.addEventListener('click', function () {
            var submenu = this.parentElement.parentElement;
            var toggle = submenu.previousElementSibling;
            if (toggle && toggle.classList.contains('menu-toggle')) {
                submenu.style.maxHeight = '0';
                var icon = toggle.querySelector('.toggle-icon');
                if (icon) icon.style.transform = 'rotate(0deg)';
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    });
});
