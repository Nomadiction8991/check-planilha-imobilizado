{{-- Barra de Ações Rápidas Mobile & Atalhos de Produtividade no PC --}}
<div class="quick-actions-container" data-quick-actions-bar role="toolbar" aria-label="Ações rápidas de navegação">
    <!-- Barra flutuante inferior para Mobile e Toolbar Desktop -->
    <nav class="quick-actions-bar" aria-label="Navegação rápida">
        <button type="button" 
                class="quick-action-btn" 
                data-quick-action="search" 
                title="Focar na busca ou código do bem (Atalho: /)"
                aria-label="Focar na busca (Atalho /)">
            <span class="material-symbols-outlined quick-action-icon" aria-hidden="true">search</span>
            <span class="quick-action-label">Buscar</span>
            <kbd class="quick-action-kbd" aria-hidden="true">/</kbd>
        </button>

        <button type="button" 
                class="quick-action-btn" 
                data-quick-action="filters" 
                title="Alternar filtros (Atalho: F)"
                aria-label="Alternar filtros (Atalho F)">
            <span class="material-symbols-outlined quick-action-icon" aria-hidden="true">filter_list</span>
            <span class="quick-action-label">Filtros</span>
            <kbd class="quick-action-kbd" aria-hidden="true">F</kbd>
        </button>

        <button type="button" 
                class="quick-action-btn" 
                data-quick-action="top" 
                title="Rolar para o topo (Atalho: T)"
                aria-label="Rolar para o topo (Atalho T)">
            <span class="material-symbols-outlined quick-action-icon" aria-hidden="true">vertical_align_top</span>
            <span class="quick-action-label">Topo</span>
            <kbd class="quick-action-kbd" aria-hidden="true">T</kbd>
        </button>

        <button type="button" 
                class="quick-action-btn" 
                data-quick-action="shortcuts" 
                title="Ver atalhos de teclado (Atalho: ?)"
                aria-label="Ver atalhos de teclado (Atalho ?)">
            <span class="material-symbols-outlined quick-action-icon" aria-hidden="true">keyboard</span>
            <span class="quick-action-label">Atalhos</span>
            <kbd class="quick-action-kbd" aria-hidden="true">?</kbd>
        </button>
    </nav>

    <!-- Modal Acessível de Atalhos de Teclado -->
    <div class="quick-shortcuts-modal" data-quick-shortcuts-modal role="dialog" aria-modal="true" aria-labelledby="quick-actions-shortcuts-title" hidden>
        <div class="quick-shortcuts-backdrop" data-quick-shortcuts-close></div>
        <div class="quick-shortcuts-dialog">
            <header class="quick-shortcuts-header">
                <h3 id="quick-actions-shortcuts-title" class="quick-shortcuts-title">
                    <span class="material-symbols-outlined" aria-hidden="true">keyboard</span>
                    Atalhos de Teclado & Navegação Rápida
                </h3>
                <button type="button" class="quick-shortcuts-close" data-quick-shortcuts-close aria-label="Fechar atalhos">
                    <span class="material-symbols-outlined" aria-hidden="true">close</span>
                </button>
            </header>
            <div class="quick-shortcuts-body">
                <ul class="quick-shortcuts-list">
                    <li>
                        <kbd>/</kbd>
                        <span>Foca diretamente no campo de busca ou código do bem</span>
                    </li>
                    <li>
                        <kbd>F</kbd>
                        <span>Abre ou recolhe a gaveta de filtros da página</span>
                    </li>
                    <li>
                        <kbd>T</kbd>
                        <span>Rola a página suavemente de volta ao topo</span>
                    </li>
                    <li>
                        <kbd>?</kbd>
                        <span>Abre esta janela de ajuda com atalhos</span>
                    </li>
                    <li>
                        <kbd>Esc</kbd>
                        <span>Fecha janelas modais ou limpa a seleção ativa</span>
                    </li>
                </ul>
                <div class="quick-shortcuts-footer">
                    <small>Dica no celular: use a barra inferior para navegar rapidamente sem precisar rolar toda a lista.</small>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.quick-actions-container {
    position: fixed;
    bottom: 1rem;
    right: 1rem;
    z-index: 50;
    pointer-events: none;
}
.quick-actions-bar {
    pointer-events: auto;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: var(--surface);
    border: 1px solid var(--border);
    padding: 0.35rem 0.5rem;
    border-radius: 9999px;
    box-shadow: var(--shadow-card);
    backdrop-filter: blur(8px);
}
.quick-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.5rem 0.75rem;
    border-radius: 9999px;
    border: none;
    background: transparent;
    color: var(--text);
    font-size: 0.8125rem;
    font-weight: 500;
    cursor: pointer;
    min-height: 44px;
    min-width: 44px;
    touch-action: manipulation;
    transition: background 0.15s ease, transform 0.1s ease;
}
.quick-action-btn:hover {
    background: var(--surface-hover);
}
.quick-action-btn:active {
    transform: scale(0.96);
}
.quick-action-icon {
    font-size: 1.25rem;
}
.quick-action-kbd {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.25rem;
    padding: 0.1rem 0.3rem;
    font-size: 0.6875rem;
    font-family: inherit;
    font-weight: 600;
    background: var(--muted);
    color: var(--muted-foreground);
    border-radius: 0.25rem;
}
@media (max-width: 768px) {
    .quick-actions-container {
        left: 0;
        right: 0;
        bottom: 0;
        border-radius: 0;
    }
    .quick-actions-bar {
        border-radius: 0;
        border-left: none;
        border-right: none;
        border-bottom: none;
        justify-content: space-around;
        width: 100%;
        padding: 0.5rem 0.25rem;
    }
    .quick-action-kbd {
        display: none;
    }
    .quick-action-btn {
        flex-direction: column;
        gap: 0.15rem;
        padding: 0.25rem;
        font-size: 0.6875rem;
    }
    .quick-action-icon {
        font-size: 1.35rem;
    }
}
.quick-shortcuts-modal[hidden] {
    display: none;
}
.quick-shortcuts-modal {
    position: fixed;
    inset: 0;
    z-index: 100;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    pointer-events: auto;
}
.quick-shortcuts-backdrop {
    position: absolute;
    inset: 0;
    background: var(--ui-overlay);
    backdrop-filter: blur(4px);
}
.quick-shortcuts-dialog {
    position: relative;
    width: 100%;
    max-width: 480px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 1rem;
    box-shadow: var(--shadow-card);
    overflow: hidden;
}
.quick-shortcuts-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border);
}
.quick-shortcuts-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
    color: var(--text);
}
.quick-shortcuts-close {
    border: none;
    background: transparent;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 0.375rem;
    color: var(--muted-foreground);
    display: flex;
}
.quick-shortcuts-body {
    padding: 1.25rem;
}
.quick-shortcuts-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}
.quick-shortcuts-list li {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.875rem;
    color: var(--text);
}
.quick-shortcuts-list kbd {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2rem;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 700;
    border: 1px solid var(--border);
    background: var(--surface-hover);
    border-radius: 0.375rem;
    box-shadow: var(--shadow-card);
}
.quick-shortcuts-footer {
    margin-top: 1.25rem;
    padding-top: 0.75rem;
    border-top: 1px dashed var(--border);
    color: var(--muted-foreground);
}
</style>

<script>
(function() {
    if (window.__quickActionsInitialized) return;
    window.__quickActionsInitialized = true;

    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.querySelector('[data-quick-shortcuts-modal]');
        const searchBtn = document.querySelector('[data-quick-action="search"]');
        const filtersBtn = document.querySelector('[data-quick-action="filters"]');
        const topBtn = document.querySelector('[data-quick-action="top"]');
        const shortcutsBtn = document.querySelector('[data-quick-action="shortcuts"]');
        const closeBtns = document.querySelectorAll('[data-quick-shortcuts-close]');

        function openShortcuts() {
            if (modal) {
                modal.removeAttribute('hidden');
            }
        }

        function closeShortcuts() {
            if (modal) {
                modal.setAttribute('hidden', '');
            }
        }

        function focusSearch() {
            const searchInput = document.querySelector('input[type="search"], input[name="search"], input[name="termo"], input[name="codigo"], input[name="q"], #search-input');
            if (searchInput) {
                searchInput.focus();
                if (typeof searchInput.select === 'function') searchInput.select();
            } else {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }

        function toggleFilters() {
            const toggle = document.querySelector('[data-product-filters-toggle], [data-filter-toggle], .product-filters-toggle');
            if (toggle) {
                toggle.click();
            }
        }

        if (searchBtn) searchBtn.addEventListener('click', focusSearch);
        if (filtersBtn) filtersBtn.addEventListener('click', toggleFilters);
        if (topBtn) topBtn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
        if (shortcutsBtn) shortcutsBtn.addEventListener('click', openShortcuts);
        closeBtns.forEach(btn => btn.addEventListener('click', closeShortcuts));

        document.addEventListener('keydown', function(e) {
            const target = e.target;
            const isEditing = target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.tagName === 'SELECT' || target.isContentEditable);

            if (e.key === 'Escape') {
                closeShortcuts();
                return;
            }

            if (isEditing) return;

            if (e.key === '/') {
                e.preventDefault();
                focusSearch();
            } else if (e.key === 'f' || e.key === 'F') {
                e.preventDefault();
                toggleFilters();
            } else if (e.key === 't' || e.key === 'T') {
                e.preventDefault();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else if (e.key === '?') {
                e.preventDefault();
                openShortcuts();
            }
        });
    });
})();
</script>
