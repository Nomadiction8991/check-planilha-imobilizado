<style>
    :root {
        --ui-bg: #F7F4ED;
        --ui-surface: #FFFDF9;
        --ui-surface-muted: #F0EBE3;
        --ui-text: #272727;
        --ui-text-muted: #60636A;
        --ui-border: #D1CDC6;
        --ui-border-strong: #77736E;
        --ui-focus: #105786;
        --product-primary: #176DA9;
        --product-primary-hover: #105786;
        --product-primary-soft: #DDECF7;
        --product-primary-text: #FFFFFF;
        --ui-success: #267454;
        --ui-success-bg: #E3F1EA;
        --ui-warning: #986500;
        --ui-warning-bg: #FBF1DC;
        --ui-danger: #B33A3A;
        --ui-danger-bg: #F8E4E4;
        --ui-overlay: rgb(39 39 39 / 35%);
        --radius-sm: 6px;
        --radius-md: 10px;
        --radius-lg: 14px;
        --shadow-card: 0 2px 8px rgb(39 39 39 / 5%);

        /* Nomes usados pelas telas existentes. */
        --bg: var(--ui-bg);
        --surface: var(--ui-surface);
        --surface-strong: var(--ui-surface);
        --surface-soft: var(--ui-surface-muted);
        --surface-hover: var(--ui-surface-muted);
        --ink: var(--ui-text);
        --text: var(--ui-text);
        --muted: var(--ui-text-muted);
        --muted-foreground: var(--ui-text-muted);
        --line: var(--ui-border);
        --border: var(--ui-border);
        --stroke-color: var(--ui-border);
        --line-strong: var(--ui-border-strong);
        --accent: var(--product-primary);
        --accent-soft: var(--product-primary-soft);
        --warn: var(--ui-warning);
        --warn-soft: var(--ui-warning-bg);
        --radius: var(--radius-md);
        --shadow: var(--shadow-card);
        --shadow-soft: var(--shadow-card);
        --shadow-strong: var(--shadow-card);
        --tone-bg: var(--ui-surface-muted);
        --tone-card: var(--ui-surface);
        --tone-line: var(--ui-border);
        --tone-text: var(--ui-text);
    }

    /* Superfícies quentes e azul claro para o tema escuro. */
    html[data-theme='dark'] {
        --ui-bg: #161513;
        --ui-surface: #1F1E1B;
        --ui-surface-muted: #282722;
        --ui-text: #F2EFE9;
        --ui-text-muted: #B9B4AA;
        --ui-border: #3A3834;
        --ui-border-strong: #77736E;
        --ui-focus: #83C7F4;
        --product-primary: #83C7F4;
        --product-primary-hover: #A3D7F7;
        --product-primary-soft: #1E3445;
        --product-primary-text: #171613;
        --ui-success: #7CC9A2;
        --ui-success-bg: #1B2D24;
        --ui-warning: #E0B45A;
        --ui-warning-bg: #33290F;
        --ui-danger: #F08A8A;
        --ui-danger-bg: #3A1F1F;
        --ui-overlay: rgb(0 0 0 / 55%);
        --shadow-card: 0 2px 8px rgb(0 0 0 / 30%);
    }

    button, input, select, textarea { font: inherit; }
</style>
