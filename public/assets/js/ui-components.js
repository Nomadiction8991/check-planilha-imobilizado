/**
 * UI COMPONENTS
 *
 * Compatível com Tailwind CSS
 * - Modal handling (hidden/flex classes)
 * - Alert dismissal
 * - Dropdown menus
 */

class UIComponents {
    /**
     * Inicializa todos os componentes
     */
    static init() {
        this.initModals();
        this.initAlerts();
        this.initDropdowns();
    }

    /**
     * Modal: data-bs-toggle="modal" data-bs-target="#id"
     * Usa classes Tailwind: hidden/flex para visibilidade
     */
    static initModals() {
        // Botões que abrem modais
        document.addEventListener('click', (e) => {
            const trigger = e.target.closest('[data-bs-toggle="modal"]');
            if (!trigger) return;

            const targetId = trigger.getAttribute('data-bs-target');
            if (!targetId) return;

            const modal = document.querySelector(targetId);
            if (modal) {
                this.openModal(modal);
            }
        });

        // Fechar modal ao clicar no .btn-close
        document.addEventListener('click', (e) => {
            if (e.target.closest('.btn-close')) {
                const modal = e.target.closest('[role="dialog"]') || e.target.closest('.modal');
                if (modal) {
                    this.closeModal(modal);
                }
            }
        });

        // Fechar modal ao clicar fora do modal-content (bg-black/50)
        document.addEventListener('click', (e) => {
            const modal = e.target.closest('[role="dialog"]') || e.target.closest('.modal');
            if (modal && !e.target.closest('.modal-content')) {
                this.closeModal(modal);
            }
        });
    }

    static openModal(modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    static closeModal(modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }

    /**
     * Alert dismissível: button[data-dismiss="alert"]
     */
    static initAlerts() {
        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-dismiss="alert"]')) {
                const alert = e.target.closest('[role="alert"]') || e.target.closest('.alert');
                if (alert) {
                    alert.remove();
                }
            }
        });
    }

    /**
     * Dropdown: data-bs-toggle="dropdown"
     * Usa classes Tailwind: hidden para toggle
     */
    static initDropdowns() {
        document.addEventListener('click', (e) => {
            const trigger = e.target.closest('[data-bs-toggle="dropdown"]');
            if (!trigger) {
                // Fechar dropdowns abertos se clicou fora
                document.querySelectorAll('.dropdown-menu:not(.hidden)').forEach((menu) => {
                    menu.classList.add('hidden');
                });
                return;
            }

            e.stopPropagation();

            const dropdown = trigger.closest('.dropdown') || trigger.parentElement;
            const menu = dropdown.querySelector('.dropdown-menu');

            if (!menu) return;

            // Toggle
            if (menu.classList.contains('hidden')) {
                // Fecha outros dropdowns
                document.querySelectorAll('.dropdown-menu:not(.hidden)').forEach((m) => {
                    m.classList.add('hidden');
                });

                menu.classList.remove('hidden');

                // Fechar ao clicar fora
                const closeHandler = (event) => {
                    if (!dropdown.contains(event.target)) {
                        menu.classList.add('hidden');
                        document.removeEventListener('click', closeHandler);
                    }
                };
                document.addEventListener('click', closeHandler);
            } else {
                menu.classList.add('hidden');
            }
        });

        // Fechar dropdown ao clicar em um item
        document.addEventListener('click', (e) => {
            if (e.target.closest('.dropdown-menu a')) {
                const menu = e.target.closest('.dropdown-menu');
                menu.classList.add('hidden');
            }
        });
    }

    /**
     * Form validation
     */
    static validateForm(formSelector) {
        const form = document.querySelector(formSelector);
        if (!form) return true;

        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');

        requiredFields.forEach((field) => {
            if (!field.value.trim()) {
                field.classList.add('ring-2', 'ring-red-500');
                isValid = false;
            } else {
                field.classList.remove('ring-2', 'ring-red-500');
            }
        });

        return isValid;
    }

    /**
     * Loading state em botões
     */
    static setLoading(buttonSelector, isLoading = true) {
        const btn = document.querySelector(buttonSelector);
        if (!btn) return;

        if (isLoading) {
            btn.disabled = true;
            btn.dataset.originalText = btn.textContent;
            btn.innerHTML = '<span class="animate-spin">⏳</span> Carregando...';
        } else {
            btn.disabled = false;
            btn.textContent = btn.dataset.originalText || btn.textContent;
        }
    }
}

// Inicializar quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    UIComponents.init();
});
