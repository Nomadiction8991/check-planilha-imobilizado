<style>
    .theme-toggle {
        display: inline-grid;
        place-items: center;
        width: 44px;
        height: 44px;
        padding: 0;
        border: 1px solid var(--line);
        border-radius: 999px;
        background: var(--ui-surface);
        color: var(--ink);
        font: inherit;
        cursor: pointer;
        box-shadow: var(--shadow-card);
        backdrop-filter: blur(10px);
        transition: transform 0.18s ease, background-color 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease, color 0.18s ease;
    }

    .theme-toggle:hover {
        transform: translateY(-1px);
        border-color: var(--ui-border-strong);
        box-shadow: var(--shadow-card);
    }

    .theme-toggle:active {
        transform: translateY(0);
        box-shadow: var(--shadow-card);
    }

    .theme-toggle:focus-visible {
        outline: 3px solid var(--ui-focus);
        outline-offset: 2px;
    }

    .theme-toggle-icon {
        font-size: 20px;
        line-height: 1;
        transition: transform 0.18s ease, color 0.18s ease;
    }

    .theme-toggle:hover .theme-toggle-icon {
        transform: scale(1.06);
    }

    .module-card {
        border-top-width: 3px;
        border-top-style: solid;
        border-top-color: var(--tone-line, var(--line));
        overflow: hidden;
    }

    .dashboard-panel {
        display: grid;
        gap: 18px;
        padding: 24px;
        border: 1px solid var(--line);
        border-radius: var(--radius-lg);
        background: var(--surface);
        box-shadow: var(--shadow-strong);
        overflow: hidden;
    }

    .module-tone {
        display: inline-flex;
        align-items: center;
        width: fit-content;
        padding: 6px 10px;
        border: 1px solid var(--tone-line, var(--line));
        border-radius: 999px;
        background: var(--tone-bg, var(--surface-soft));
        color: var(--tone-text, var(--ink));
        font-size: 12px;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .palette-legend {
        display: grid;
        gap: 14px;
        padding: 18px 20px;
        margin-top: 18px;
        border: 1px solid var(--line);
        border-radius: var(--radius);
        background: var(--surface-soft);
    }

    .palette-legend-head {
        display: grid;
        gap: 4px;
    }

    .palette-legend-head strong {
        font-size: 14px;
        letter-spacing: -0.01em;
    }

    .palette-legend-head span {
        color: var(--muted);
        line-height: 1.45;
    }

    .palette-legend-grid {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    }

    .palette-legend-item {
        display: grid;
        gap: 8px;
        align-content: start;
    }

    .palette-legend-item p {
        margin: 0;
        color: var(--muted);
        line-height: 1.45;
    }

    input::placeholder,
    textarea::placeholder {
        color: var(--muted);
        opacity: 1;
    }

    @media (max-width: 860px) {
        .theme-toggle {
            width: 40px;
            height: 40px;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .theme-toggle,
        .theme-toggle-icon {
            transition: none;
        }
    }
</style>
<script>
    (() => {
        const storageKey = 'check-planilha-theme';
        const root = document.documentElement;

        function safeGetTheme() {
            try {
                const storedTheme = localStorage.getItem(storageKey);
                if (storedTheme === 'dark' || storedTheme === 'light') {
                    return storedTheme;
                }
            } catch (error) {
            }

            return root.dataset.theme === 'dark' ? 'dark' : 'light';
        }

        function applyTheme(theme) {
            root.dataset.theme = theme;
            root.style.colorScheme = theme;

            try {
                localStorage.setItem(storageKey, theme);
            } catch (error) {
            }

            document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
                const icon = button.querySelector('[data-theme-icon]');
                const darkTheme = theme === 'dark';

                button.setAttribute('aria-pressed', darkTheme ? 'true' : 'false');
                button.setAttribute('aria-label', darkTheme ? 'Ativar tema claro' : 'Ativar tema escuro');

                if (icon) {
                    icon.textContent = darkTheme ? 'light_mode' : 'dark_mode';
                }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            applyTheme(safeGetTheme());
        });

        document.addEventListener('click', (event) => {
            const toggle = event.target.closest('[data-theme-toggle]');

            if (!toggle) {
                return;
            }

            applyTheme(root.dataset.theme === 'dark' ? 'light' : 'dark');
        });
    })();
</script>
