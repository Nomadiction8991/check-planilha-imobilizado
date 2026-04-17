<style>
    .theme-toggle {
        display: inline-grid;
        place-items: center;
        width: 44px;
        height: 44px;
        padding: 0;
        border: 1px solid var(--line);
        border-radius: 999px;
        background:
            radial-gradient(circle at 30% 28%, rgba(255, 255, 255, 0.36), transparent 52%),
            linear-gradient(180deg, rgba(255, 255, 255, 0.86), rgba(247, 241, 233, 0.82));
        color: var(--ink);
        font: inherit;
        cursor: pointer;
        box-shadow:
            0 1px 0 rgba(24, 21, 17, 0.04),
            0 8px 18px rgba(38, 28, 12, 0.06);
        backdrop-filter: blur(10px);
        transition: transform 0.18s ease, background-color 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease, color 0.18s ease;
    }

    .theme-toggle:hover {
        transform: translateY(-1px);
        border-color: rgba(24, 21, 17, 0.18);
        box-shadow: 0 10px 22px rgba(38, 28, 12, 0.1);
    }

    .theme-toggle:active {
        transform: translateY(0);
        box-shadow: 0 4px 10px rgba(38, 28, 12, 0.08);
    }

    .theme-toggle:focus-visible {
        outline: none;
        box-shadow:
            0 0 0 3px rgba(31, 111, 95, 0.18),
            0 10px 22px rgba(38, 28, 12, 0.08);
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
        border: 1px solid var(--line);
        border-radius: var(--radius);
        background: var(--surface);
        box-shadow: var(--shadow-soft);
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

    html[data-theme='light'] {
        --bg: #f6efe7;
        --surface: rgba(255, 252, 247, 0.93);
        --surface-strong: #fffdf8;
        --surface-soft: rgba(255, 255, 255, 0.76);
        --ink: #191410;
        --muted: #695d4e;
        --line: rgba(24, 21, 17, 0.10);
        --accent: #1f6f5f;
        --accent-soft: rgba(31, 111, 95, 0.12);
        --warn: #8b3d19;
        --warn-soft: rgba(139, 61, 25, 0.12);
        --shadow-soft: 0 10px 24px rgba(38, 28, 12, 0.07);
        --shadow: 0 16px 38px rgba(38, 28, 12, 0.08);
        --shadow-strong: 0 24px 60px rgba(38, 28, 12, 0.12);
        color-scheme: light;
    }

    html[data-theme='light'] body {
        background:
            radial-gradient(circle at top left, rgba(31, 111, 95, 0.11), transparent 28%),
            radial-gradient(circle at bottom right, rgba(181, 125, 78, 0.12), transparent 25%),
            linear-gradient(180deg, #fbf7f2 0%, var(--bg) 100%);
    }

    html[data-theme='light'] ::selection {
        background: rgba(31, 111, 95, 0.18);
        color: #0f1614;
    }

    html[data-theme='light'] .module-tone--structure {
        --tone-bg: rgba(111, 100, 88, 0.12);
        --tone-text: #5f554a;
        --tone-line: rgba(111, 100, 88, 0.24);
        --tone-card: linear-gradient(180deg, rgba(111, 100, 88, 0.08), rgba(255, 252, 247, 0.98));
    }

    html[data-theme='light'] .module-tone--catalog {
        --tone-bg: rgba(86, 116, 63, 0.14);
        --tone-text: #4f6b39;
        --tone-line: rgba(86, 116, 63, 0.24);
        --tone-card: linear-gradient(180deg, rgba(86, 116, 63, 0.08), rgba(255, 252, 247, 0.98));
    }

    html[data-theme='light'] .module-tone--inventory {
        --tone-bg: rgba(31, 111, 95, 0.14);
        --tone-text: #1f6f5f;
        --tone-line: rgba(31, 111, 95, 0.24);
        --tone-card: linear-gradient(180deg, rgba(31, 111, 95, 0.08), rgba(255, 252, 247, 0.98));
    }

    html[data-theme='light'] .module-tone--access {
        --tone-bg: rgba(69, 105, 165, 0.14);
        --tone-text: #35558f;
        --tone-line: rgba(69, 105, 165, 0.24);
        --tone-card: linear-gradient(180deg, rgba(69, 105, 165, 0.08), rgba(255, 252, 247, 0.98));
    }

    html[data-theme='light'] .module-tone--flow {
        --tone-bg: rgba(177, 118, 60, 0.14);
        --tone-text: #915a25;
        --tone-line: rgba(177, 118, 60, 0.24);
        --tone-card: linear-gradient(180deg, rgba(177, 118, 60, 0.08), rgba(255, 252, 247, 0.98));
    }

    html[data-theme='light'] .module-tone--audit {
        --tone-bg: rgba(88, 103, 133, 0.14);
        --tone-text: #4b5d7a;
        --tone-line: rgba(88, 103, 133, 0.24);
        --tone-card: linear-gradient(180deg, rgba(88, 103, 133, 0.08), rgba(255, 252, 247, 0.98));
    }

    html[data-theme='light'] .module-card.module-tone--structure,
    html[data-theme='light'] .module-card.module-tone--catalog,
    html[data-theme='light'] .module-card.module-tone--inventory,
    html[data-theme='light'] .module-card.module-tone--access,
    html[data-theme='light'] .module-card.module-tone--flow,
    html[data-theme='light'] .module-card.module-tone--audit {
        background: var(--tone-card, var(--surface));
    }

    html[data-theme='light'] .topbar,
    html[data-theme='light'] .hero,
    html[data-theme='light'] .panel,
    html[data-theme='light'] .table-shell,
    html[data-theme='light'] .helper,
    html[data-theme='light'] .filters,
    html[data-theme='light'] .metric,
    html[data-theme='light'] .flash,
    html[data-theme='light'] .module-card,
    html[data-theme='light'] .stat-card,
    html[data-theme='light'] .pagination,
    html[data-theme='light'] .card,
    html[data-theme='light'] .section-card {
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.90), rgba(255, 252, 247, 0.94));
        border-color: var(--line);
        box-shadow: var(--shadow);
    }

    html[data-theme='light'] .topbar,
    html[data-theme='light'] .hero {
        box-shadow: var(--shadow-strong);
    }

    html[data-theme='light'] .nav a,
    html[data-theme='light'] .btn,
    html[data-theme='light'] .pagination a,
    html[data-theme='light'] .pagination span,
    html[data-theme='light'] .capsule,
    html[data-theme='light'] .module-badge,
    html[data-theme='light'] .status,
    html[data-theme='light'] .report-toggle,
    html[data-theme='light'] input,
    html[data-theme='light'] select,
    html[data-theme='light'] textarea {
        background: rgba(255, 255, 255, 0.76);
        border-color: var(--line);
        color: var(--ink);
    }

    html[data-theme='light'] .nav a:hover,
    html[data-theme='light'] .btn:not(.theme-toggle):hover,
    html[data-theme='light'] .pagination a:hover,
    html[data-theme='light'] button:not(.theme-toggle):not(.session-logout):not(.mobile-menu-logout):hover {
        border-color: rgba(24, 21, 17, 0.16);
        box-shadow: 0 8px 20px rgba(38, 28, 12, 0.08);
    }

    html[data-theme='light'] .nav a.active,
    html[data-theme='light'] .eyebrow {
        color: #0f1614;
        background: var(--accent-soft);
        border-color: rgba(31, 111, 95, 0.20);
    }

    html[data-theme='light'] .btn.primary,
    html[data-theme='light'] button:not(.theme-toggle):not(.session-logout):not(.mobile-menu-logout) {
        color: #fffdf8;
        background: linear-gradient(180deg, #235d51 0%, #1f6f5f 100%);
        border-color: rgba(31, 111, 95, 0.22);
        box-shadow: 0 8px 18px rgba(31, 111, 95, 0.14);
    }

    html[data-theme='light'] .btn.primary:hover,
    html[data-theme='light'] button:not(.theme-toggle):not(.session-logout):not(.mobile-menu-logout):hover {
        background: linear-gradient(180deg, #2a6b5d 0%, #247163 100%);
    }

    html[data-theme='light'] select {
        color-scheme: light;
    }

    html[data-theme='light'] select option,
    html[data-theme='light'] select optgroup {
        color: var(--ink);
        background: #fffdf8;
    }

    html[data-theme='light'] select option:checked {
        background: rgba(31, 111, 95, 0.16);
    }

    html[data-theme='light'] th {
        background: rgba(255, 255, 255, 0.80);
    }

    html[data-theme='light'] table {
        background: rgba(255, 255, 255, 0.34);
    }

    html[data-theme='light'] tbody tr:hover td {
        background: rgba(31, 111, 95, 0.04);
    }

    html[data-theme='light'] .capsule.dark {
        color: #fffdf8;
        background: var(--ink);
    }

    html[data-theme='light'] .capsule.accent,
    html[data-theme='light'] .status {
        color: var(--accent);
        background: var(--accent-soft);
    }

    html[data-theme='light'] .capsule.warn,
    html[data-theme='light'] .status.warn {
        color: var(--warn);
        background: var(--warn-soft);
    }

    html[data-theme='light'] .pagination a,
    html[data-theme='light'] .pagination span {
        background: rgba(255, 255, 255, 0.80);
    }

    html[data-theme='light'] .flash.success {
        background: rgba(31, 111, 95, 0.08);
    }

    html[data-theme='light'] .flash.error {
        background: rgba(139, 61, 25, 0.08);
    }

    html[data-theme='light'] .theme-toggle {
        background:
            radial-gradient(circle at 30% 28%, rgba(31, 111, 95, 0.08), transparent 50%),
            linear-gradient(180deg, rgba(255, 255, 255, 0.90), rgba(248, 243, 236, 0.86));
        border-color: rgba(24, 21, 17, 0.10);
        box-shadow:
            0 1px 0 rgba(24, 21, 17, 0.04),
            0 6px 16px rgba(38, 28, 12, 0.05);
    }

    html[data-theme='light'] .theme-toggle:hover {
        background:
            radial-gradient(circle at 30% 28%, rgba(31, 111, 95, 0.12), transparent 50%),
            linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(248, 243, 236, 0.90));
    }

    html[data-theme='light'] .theme-toggle .material-symbols-outlined {
        color: var(--accent);
    }

    html[data-theme='light'] .session-logout {
        border-color: rgba(139, 61, 25, 0.20);
        background:
            radial-gradient(circle at 30% 28%, rgba(139, 61, 25, 0.08), transparent 50%),
            linear-gradient(180deg, rgba(255, 247, 243, 0.94), rgba(245, 228, 221, 0.86));
        color: var(--warn);
        box-shadow:
            0 1px 0 rgba(24, 21, 17, 0.04),
            0 8px 18px rgba(139, 61, 25, 0.08);
    }

    html[data-theme='light'] .session-logout:hover {
        border-color: rgba(139, 61, 25, 0.28);
        background:
            radial-gradient(circle at 30% 28%, rgba(139, 61, 25, 0.12), transparent 50%),
            linear-gradient(180deg, rgba(255, 242, 237, 0.98), rgba(241, 218, 209, 0.90));
        box-shadow: 0 10px 22px rgba(139, 61, 25, 0.12);
    }

    html[data-theme='light'] .session-logout:focus-visible {
        outline: none;
        box-shadow:
            0 0 0 3px rgba(139, 61, 25, 0.18),
            0 10px 22px rgba(139, 61, 25, 0.12);
    }

    html[data-theme='dark'] {
        --bg: #11100f;
        --surface: rgba(31, 29, 26, 0.95);
        --surface-strong: #26231f;
        --surface-soft: rgba(255, 255, 255, 0.06);
        --ink: #f8f3eb;
        --muted: #c4baa9;
        --line: rgba(255, 255, 255, 0.12);
        --accent: #91ddd1;
        --accent-soft: rgba(145, 221, 209, 0.18);
        --warn: #efbe82;
        --warn-soft: rgba(239, 190, 130, 0.16);
        --shadow-soft: 0 10px 22px rgba(0, 0, 0, 0.24);
        --shadow: 0 18px 38px rgba(0, 0, 0, 0.28);
        --shadow-strong: 0 26px 54px rgba(0, 0, 0, 0.34);
        color-scheme: dark;
    }

    html[data-theme='dark'] body {
        background:
            radial-gradient(circle at top left, rgba(145, 221, 209, 0.16), transparent 28%),
            radial-gradient(circle at bottom right, rgba(239, 190, 130, 0.12), transparent 24%),
            linear-gradient(180deg, #181612 0%, var(--bg) 100%);
    }

    html[data-theme='dark'] ::selection {
        background: rgba(132, 209, 196, 0.24);
        color: #f4ede4;
    }

    html[data-theme='dark'] .module-tone--structure {
        --tone-bg: rgba(214, 202, 185, 0.12);
        --tone-text: #efe8dc;
        --tone-line: rgba(214, 202, 185, 0.22);
        --tone-card: linear-gradient(180deg, rgba(214, 202, 185, 0.08), rgba(35, 33, 29, 0.98));
    }

    html[data-theme='dark'] .module-tone--catalog {
        --tone-bg: rgba(157, 195, 116, 0.14);
        --tone-text: #d7e8b8;
        --tone-line: rgba(157, 195, 116, 0.22);
        --tone-card: linear-gradient(180deg, rgba(157, 195, 116, 0.08), rgba(35, 33, 29, 0.98));
    }

    html[data-theme='dark'] .module-tone--inventory {
        --tone-bg: rgba(145, 221, 209, 0.14);
        --tone-text: #d7fff7;
        --tone-line: rgba(145, 221, 209, 0.24);
        --tone-card: linear-gradient(180deg, rgba(145, 221, 209, 0.08), rgba(35, 33, 29, 0.98));
    }

    html[data-theme='dark'] .module-tone--access {
        --tone-bg: rgba(129, 167, 232, 0.16);
        --tone-text: #dce7ff;
        --tone-line: rgba(129, 167, 232, 0.24);
        --tone-card: linear-gradient(180deg, rgba(129, 167, 232, 0.08), rgba(35, 33, 29, 0.98));
    }

    html[data-theme='dark'] .module-tone--flow {
        --tone-bg: rgba(239, 190, 130, 0.16);
        --tone-text: #ffe2b8;
        --tone-line: rgba(239, 190, 130, 0.24);
        --tone-card: linear-gradient(180deg, rgba(239, 190, 130, 0.08), rgba(35, 33, 29, 0.98));
    }

    html[data-theme='dark'] .module-tone--audit {
        --tone-bg: rgba(162, 178, 214, 0.16);
        --tone-text: #d9e3ff;
        --tone-line: rgba(162, 178, 214, 0.24);
        --tone-card: linear-gradient(180deg, rgba(162, 178, 214, 0.08), rgba(35, 33, 29, 0.98));
    }

    html[data-theme='dark'] .module-card.module-tone--structure,
    html[data-theme='dark'] .module-card.module-tone--catalog,
    html[data-theme='dark'] .module-card.module-tone--inventory,
    html[data-theme='dark'] .module-card.module-tone--access,
    html[data-theme='dark'] .module-card.module-tone--flow,
    html[data-theme='dark'] .module-card.module-tone--audit {
        background: var(--tone-card, var(--surface));
    }

    html[data-theme='dark'] .topbar,
    html[data-theme='dark'] .hero,
    html[data-theme='dark'] .panel,
    html[data-theme='dark'] .table-shell,
    html[data-theme='dark'] .helper,
    html[data-theme='dark'] .filters,
    html[data-theme='dark'] .metric,
    html[data-theme='dark'] .flash,
    html[data-theme='dark'] .module-card,
    html[data-theme='dark'] .stat-card,
    html[data-theme='dark'] .pagination,
    html[data-theme='dark'] .card,
    html[data-theme='dark'] .section-card {
        background: linear-gradient(180deg, rgba(35, 33, 29, 0.98), rgba(26, 24, 21, 0.96));
        border-color: var(--line);
        box-shadow: var(--shadow);
    }

    html[data-theme='dark'] .topbar,
    html[data-theme='dark'] .hero {
        box-shadow: var(--shadow-strong);
    }

    html[data-theme='dark'] .nav a,
    html[data-theme='dark'] .btn,
    html[data-theme='dark'] .pagination a,
    html[data-theme='dark'] .pagination span,
    html[data-theme='dark'] .capsule,
    html[data-theme='dark'] .module-badge,
    html[data-theme='dark'] .status,
    html[data-theme='dark'] .report-toggle,
    html[data-theme='dark'] input,
    html[data-theme='dark'] select,
    html[data-theme='dark'] textarea {
        background: rgba(255, 255, 255, 0.06);
        border-color: var(--line);
        color: var(--ink);
    }

    html[data-theme='dark'] .nav a:hover,
    html[data-theme='dark'] .btn:not(.theme-toggle):hover,
    html[data-theme='dark'] .pagination a:hover,
    html[data-theme='dark'] button:not(.theme-toggle):not(.session-logout):not(.mobile-menu-logout):hover {
        border-color: rgba(255, 255, 255, 0.22);
        box-shadow: 0 10px 22px rgba(0, 0, 0, 0.26);
    }

    html[data-theme='dark'] .nav a.active,
    html[data-theme='dark'] .eyebrow {
        color: #effcf8;
        background: linear-gradient(180deg, rgba(145, 221, 209, 0.28), rgba(145, 221, 209, 0.18));
        border-color: rgba(145, 221, 209, 0.34);
        box-shadow: 0 8px 18px rgba(9, 31, 26, 0.18);
    }

    html[data-theme='dark'] .btn.primary,
    html[data-theme='dark'] button:not(.theme-toggle):not(.session-logout):not(.mobile-menu-logout) {
        color: #0d1614;
        background: linear-gradient(180deg, #b0e9de 0%, #84d8c9 100%);
        border-color: rgba(145, 221, 209, 0.30);
        box-shadow: 0 10px 20px rgba(9, 31, 26, 0.26);
    }

    html[data-theme='dark'] .btn.primary:hover,
    html[data-theme='dark'] button:not(.theme-toggle):not(.session-logout):not(.mobile-menu-logout):hover {
        background: linear-gradient(180deg, #bbefe6 0%, #93dfd1 100%);
    }

    html[data-theme='dark'] select {
        color-scheme: dark;
    }

    html[data-theme='dark'] select option,
    html[data-theme='dark'] select optgroup {
        color: var(--ink);
        background: var(--surface-strong);
    }

    html[data-theme='dark'] select option:checked {
        background: rgba(145, 221, 209, 0.22);
    }

    html[data-theme='dark'] select option:disabled {
        color: var(--muted);
    }

    html[data-theme='dark'] th {
        background: rgba(255, 255, 255, 0.05);
    }

    html[data-theme='dark'] table {
        background: rgba(255, 255, 255, 0.02);
    }

    html[data-theme='dark'] tbody tr:hover td {
        background: rgba(132, 209, 196, 0.06);
    }

    html[data-theme='dark'] .capsule.dark {
        color: #f4ede4;
        background: rgba(255, 255, 255, 0.10);
    }

    html[data-theme='dark'] .capsule.accent,
    html[data-theme='dark'] .status {
        color: var(--accent);
        background: rgba(145, 221, 209, 0.14);
    }

    html[data-theme='dark'] .capsule.warn,
    html[data-theme='dark'] .status.warn {
        color: var(--warn);
        background: rgba(239, 190, 130, 0.14);
    }

    html[data-theme='dark'] .pagination a,
    html[data-theme='dark'] .pagination span {
        background: rgba(255, 255, 255, 0.03);
    }

    html[data-theme='dark'] .flash.success {
        background: rgba(132, 209, 196, 0.08);
    }

    html[data-theme='dark'] .flash.error {
        background: rgba(226, 173, 114, 0.10);
    }

    html[data-theme='dark'] .theme-toggle {
        background:
            radial-gradient(circle at 30% 28%, rgba(145, 221, 209, 0.14), transparent 50%),
            linear-gradient(180deg, rgba(39, 37, 33, 0.98), rgba(24, 23, 20, 0.94));
        color: var(--ink);
        border-color: rgba(255, 255, 255, 0.12);
        box-shadow:
            0 1px 0 rgba(255, 255, 255, 0.04),
            0 10px 24px rgba(0, 0, 0, 0.26);
    }

    html[data-theme='dark'] .theme-toggle:hover {
        background:
            radial-gradient(circle at 30% 28%, rgba(145, 221, 209, 0.20), transparent 50%),
            linear-gradient(180deg, rgba(44, 41, 37, 0.98), rgba(28, 26, 23, 0.94));
        border-color: rgba(145, 221, 209, 0.22);
    }

    html[data-theme='dark'] .theme-toggle:focus-visible {
        box-shadow:
            0 0 0 3px rgba(145, 221, 209, 0.20),
            0 10px 24px rgba(0, 0, 0, 0.26);
    }

    html[data-theme='dark'] .theme-toggle .material-symbols-outlined {
        color: #d7fff7;
    }

    html[data-theme='dark'] .session-logout {
        border-color: rgba(239, 190, 130, 0.22);
        background:
            radial-gradient(circle at 30% 28%, rgba(239, 190, 130, 0.10), transparent 50%),
            linear-gradient(180deg, rgba(52, 29, 23, 0.96), rgba(33, 21, 18, 0.94));
        color: #ffb39c;
        box-shadow:
            0 1px 0 rgba(255, 255, 255, 0.04),
            0 10px 24px rgba(0, 0, 0, 0.24);
    }

    html[data-theme='dark'] .session-logout:hover {
        border-color: rgba(239, 190, 130, 0.30);
        background:
            radial-gradient(circle at 30% 28%, rgba(239, 190, 130, 0.14), transparent 50%),
            linear-gradient(180deg, rgba(61, 34, 27, 0.98), rgba(38, 23, 18, 0.96));
        box-shadow: 0 12px 26px rgba(0, 0, 0, 0.28);
    }

    html[data-theme='dark'] .session-logout:focus-visible {
        outline: none;
        box-shadow:
            0 0 0 3px rgba(239, 190, 130, 0.20),
            0 12px 26px rgba(0, 0, 0, 0.28);
    }

    html[data-theme='dark'] .session-logout .material-symbols-outlined {
        color: #ffb39c;
    }

    input::placeholder,
    textarea::placeholder {
        color: var(--muted);
        opacity: 0.72;
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
