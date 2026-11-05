<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    @include('partials.theme-init')
    @include('partials.pwa')
    <style>
        :root {
            --topbar-sticky-gap: 12px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Georgia, "Times New Roman", serif;
            color: var(--ink);
            background: var(--ui-bg);
        }

        a {
            color: inherit;
        }

        .skip-link {
            position: fixed;
            inset-block-start: 12px;
            inset-inline-start: 12px;
            z-index: 200;
            padding: 12px 16px;
            border: 1px solid var(--accent);
            border-radius: 12px;
            background: var(--surface-strong);
            color: var(--ink);
            box-shadow: var(--shadow-strong);
            transform: translateY(-180%);
            transition: transform 0.18s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .skip-link:focus-visible {
            transform: translateY(0);
        }

        a:focus-visible,
        button:focus-visible,
        input:focus-visible,
        select:focus-visible,
        textarea:focus-visible {
            outline: 3px solid var(--accent);
            outline-offset: 3px;
        }

        input[type="checkbox"]:focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 3px;
        }

        @media (prefers-reduced-motion: reduce) {
            .skip-link,
            .skip-link:focus-visible {
                transition: none;
            }
        }

        .shell {
            width: min(1240px, calc(100% - 28px));
            margin: 0 auto;
            padding: 28px 0 56px;
        }

        .sticky-stack {
            display: grid;
            gap: 12px;
        }

        .topbar,
        .panel,
        .table-shell,
        .helper,
        .filters,
        .metric,
        .flash,
        .module-card,
        .stat-card,
        .pagination {
            border: 1px solid var(--line);
            background: var(--surface);
            box-shadow: var(--shadow);
            border-radius: var(--radius);
        }

        .topbar {
            display: grid;
            gap: 0;
            padding: 16px 20px;
            border: 0;
            border-radius: var(--radius-lg);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            box-shadow: none;
        }

        .topbar-main {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: nowrap;
            min-width: 0;
        }

        .topbar-tools {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: nowrap;
            justify-content: flex-end;
            min-width: 0;
        }

        .topbar-nav {
            padding-top: 14px;
            border-top: 1px solid var(--line);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand small,
        .eyebrow,
        .nav a,
        .metric-label {
            font-size: 12px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .brand small,
        .eyebrow,
        .metric-label {
            color: var(--muted);
        }

        .brand strong {
            font-size: 19px;
            letter-spacing: -0.01em;
        }

        .nav {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .nav a {
            display: inline-flex;
            padding: 9px 14px;
            text-decoration: none;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: var(--ui-surface);
            min-height: 42px;
            align-items: center;
            transition: background-color 0.16s ease, border-color 0.16s ease, color 0.16s ease, transform 0.16s ease;
        }

        .nav a:hover {
            color: var(--ink);
            background: var(--ui-surface);
            border-color: var(--ui-border-strong);
            transform: translateY(-1px);
        }

        .nav a.active {
            color: var(--accent);
            background: var(--accent-soft);
            border-color: var(--ui-border-strong);
        }

        .session-tools {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: nowrap;
            justify-content: flex-end;
            min-width: 0;
        }

        .session-actions {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex: 0 0 auto;
            white-space: nowrap;
            order: 2;
        }

        body:has(dialog[open]) .page-quick-actions { display: none; }

        .page-quick-actions {
            position: fixed;
            right: calc(16px + env(safe-area-inset-right, 0px));
            bottom: calc(16px + env(safe-area-inset-bottom, 0px));
            z-index: 74;
            display: grid;
            justify-items: end;
            gap: 8px;
        }

        .page-quick-actions[hidden] {
            display: none !important;
        }

        .page-quick-actions-panel {
            display: grid;
            gap: 8px;
            justify-items: end;
            padding: 10px;
            border: 1px solid var(--line);
            border-radius: 18px;
            background: var(--ui-surface);
            box-shadow: var(--shadow-strong);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            opacity: 0;
            pointer-events: none;
            transform: translateY(8px) scale(0.98);
            transform-origin: bottom right;
            transition: opacity 0.18s ease, transform 0.18s ease;
        }

        .page-quick-actions.is-open .page-quick-actions-panel {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0) scale(1);
        }

        .page-quick-actions-toggle,
        .page-quick-actions-item {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 44px;
            padding: 12px 14px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: var(--ui-surface);
            color: var(--ink);
            box-shadow: var(--shadow-card);
            cursor: pointer;
            transition: background-color 0.16s ease, border-color 0.16s ease, color 0.16s ease, transform 0.16s ease, box-shadow 0.16s ease;
        }

        .page-quick-actions-toggle:hover,
        .page-quick-actions-item:hover {
            transform: translateY(-1px);
            border-color: var(--ui-border-strong);
            box-shadow: var(--shadow-card);
        }

        .page-quick-actions-toggle {
            min-width: 132px;
            background: var(--product-primary);
            color: var(--product-primary-text);
        }

        .page-quick-actions-toggle:hover { background: var(--product-primary-hover); }

        .page-quick-actions-toggle .material-symbols-outlined,
        .page-quick-actions-item .material-symbols-outlined {
            font-size: 20px;
            line-height: 1;
        }

        .page-quick-actions-toggle-label,
        .page-quick-actions-label {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: -0.01em;
        }

        .page-quick-actions-item .page-quick-actions-label { color: var(--ui-text); }
        .page-quick-actions-item.is-primary .page-quick-actions-label { color: var(--product-primary-text); }
        .page-quick-actions-toggle .page-quick-actions-toggle-label { color: var(--product-primary-text); }

        .page-quick-actions-item {
            min-width: 132px;
            justify-content: flex-start;
        }

        .page-quick-actions-item.is-primary {
            color: var(--product-primary-text);
            background: var(--product-primary);
            border-color: var(--ui-border-strong);
        }

        .page-quick-actions-item.is-active {
            color: var(--ui-text);
            background: var(--accent-soft);
            border-color: var(--ui-border-strong);
        }

        .page-quick-actions-item.is-active .material-symbols-outlined {
            color: var(--ui-text);
        }

        .page-quick-actions-item.is-pinned {
            color: var(--ui-text);
            background: var(--accent-soft);
            border-color: var(--ui-border-strong);
        }

        @media (min-width: 861px) {
            .page-quick-actions-item--voice {
                display: none !important;
            }
        }

        .page-quick-actions-item--voice.is-listening {
            border-color: var(--ui-border-strong);
            background: var(--accent-soft);
            color: var(--accent);
            animation: page-quick-actions-pulse 1.2s ease-in-out infinite;
        }

        .page-quick-actions-item--voice.is-listening .material-symbols-outlined {
            color: var(--accent);
        }

        @keyframes page-quick-actions-pulse {
            0%,
            100% {
                box-shadow: var(--shadow-card);
            }
            50% {
                box-shadow: var(--shadow-card);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .page-quick-actions-item--voice.is-listening {
                animation: none;
            }
        }

        .session-tools form {
            margin: 0;
        }

        .session-user {
            display: grid;
            gap: 2px;
            min-width: 0;
            justify-items: end;
            text-align: right;
        }

        .session-user strong {
            font-size: 14px;
        }

        .session-user small {
            color: var(--muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 160px;
        }

        .session-logout {
            display: inline-grid;
            place-items: center;
            width: 44px;
            height: 44px;
            padding: 0;
            border: 1px solid var(--ui-border-strong);
            border-radius: 999px;
            background: var(--ui-surface-muted);
            color: var(--warn);
            box-shadow: var(--shadow-card);
        }

        .session-logout--public {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: auto;
            min-width: 44px;
            padding: 0 14px;
            gap: 8px;
        }

        .session-logout-label {
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
        }

        .session-logout .material-symbols-outlined {
            font-size: 20px;
            line-height: 1;
        }

        button.menu-toggle.theme-toggle {
            display: none;
        }

        input[type="checkbox"] {
            width: 18px;
            min-width: 18px;
            height: 18px;
            min-height: 18px;
            padding: 0;
            margin: 0;
            border-radius: 4px;
            accent-color: var(--accent);
            background: transparent;
            box-shadow: none;
        }

        input[type="checkbox"]:focus {
            outline: 2px solid var(--ui-focus);
            outline-offset: 2px;
            box-shadow: none;
        }

        .check-inline {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: fit-content;
            color: var(--ink);
            font-size: 14px;
            letter-spacing: 0;
            text-transform: none;
        }

        .check-inline--block {
            grid-column: 1 / -1;
            margin-top: -2px;
            padding-top: 2px;
        }

        .hero {
            display: grid;
            gap: 12px;
            margin-top: 20px;
        }

        body.page-operational .hero {
            display: none;
        }

        body.page-operational .shell {
            padding-top: 24px;
        }

        h1,
        h2,
        h3,
        p {
            margin: 0;
        }

        h1 {
            font-size: clamp(30px, 5vw, 54px);
            line-height: 1;
            max-width: 100%;
            text-wrap: balance;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: fit-content;
            padding: 6px 10px;
            color: var(--accent);
            background: var(--accent-soft);
            border-radius: 999px;
        }

        .hero-copy,
        .table-note,
        .helper,
        .metric-copy,
        .empty-state,
        .panel-copy,
        .module-copy,
        .stat-copy {
            color: var(--muted);
            line-height: 1.55;
        }

        .hero-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 44px;
            padding: 12px 16px;
            border: 1px solid var(--line);
            border-radius: 12px;
            text-decoration: none;
            background: var(--surface-strong);
            color: var(--ui-text);
            transition: background-color 0.16s ease, border-color 0.16s ease, color 0.16s ease, transform 0.16s ease, box-shadow 0.16s ease;
            box-shadow: var(--shadow-card);
            cursor: pointer;
        }

        .btn:hover {
            transform: translateY(-1px);
            border-color: var(--ui-border-strong);
            box-shadow: var(--shadow-card);
        }

        .btn.primary {
            color: var(--product-primary-text);
            background: var(--product-primary);
            border-color: var(--ui-border-strong);
        }

        .btn.primary:hover {
            color: var(--product-primary-text);
            background: var(--product-primary-hover);
        }

        .section {
            margin-top: 24px;
        }

        .section-head {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .section-head h2, .section-head p { line-height: 1.15; }

        .metrics {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            margin-top: 22px;
        }

        .meta-grid,
        .module-grid,
        .stats-grid {
            display: grid;
            gap: 14px;
            margin-top: 22px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .meta-grid {
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        }

        .module-grid {
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        }

        .metric {
            padding: 20px;
            display: grid;
            gap: 8px;
            align-content: start;
        }

        .panel,
        .module-card,
        .stat-card {
            padding: 20px;
            display: grid;
            gap: 8px;
            align-content: start;
        }

        .panel,
        .module-card {
            min-height: 140px;
        }

        .panel-label,
        .stat-label,
        .module-badge {
            font-size: 12px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .panel-label,
        .stat-label {
            color: var(--muted);
        }

        .panel-value,
        .stat-value {
            font-size: 24px;
            line-height: 1.08;
            font-weight: 700;
            margin-top: 0;
        }

        .module-badge {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            padding: 6px 10px;
            border: 1px solid var(--ui-border-strong);
            border-radius: 999px;
            color: var(--ink);
            background: var(--ui-surface);
        }

        .status {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            padding: 6px 10px;
            border: 1px solid var(--ui-border-strong);
            border-radius: 999px;
            color: var(--accent);
            background: var(--accent-soft);
        }

        .status.warn {
            color: var(--warn);
            border-color: var(--ui-border-strong);
            background: var(--warn-soft);
        }

        .panel-copy,
        .module-copy,
        .stat-copy {
            max-width: 40ch;
        }

        .permissions-panel {
            padding: 20px;
            display: grid;
            gap: 18px;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            background: var(--surface);
            box-shadow: var(--shadow-soft);
        }

        .permissions-group {
            display: grid;
            gap: 12px;
            padding: 18px;
            border: 1px solid var(--line);
            border-radius: 18px;
            background: var(--surface-strong);
        }

        .permissions-group-head {
            display: grid;
            gap: 4px;
        }

        .permissions-group-head strong {
            font-size: 18px;
            line-height: 1.15;
        }

        .permissions-group-head p {
            color: var(--muted);
            line-height: 1.5;
        }

        .permissions-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .permission-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 14px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: var(--surface);
        }

        .permission-item strong {
            display: block;
            font-size: 14px;
            line-height: 1.2;
        }

        .permission-item small {
            display: block;
            margin-top: 3px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.35;
        }

        .metric-value {
            margin-top: 0;
            font-size: 30px;
            font-weight: 700;
            line-height: 1.06;
        }

        .filters {
            padding: 16px;
            gap: 12px;
            z-index: 55;
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            box-shadow: var(--shadow-soft);
            transition: box-shadow 0.18s ease, transform 0.18s ease, border-color 0.18s ease, background-color 0.18s ease;
        }

        .filters.is-pinned {
            box-shadow: var(--shadow-strong);
        }

        .filters form {
            display: grid;
            gap: 10px;
            grid-template-columns: 1fr;
            width: 100%;
        }

        .filters-primary,
        .filters-advanced {
            display: grid;
            gap: 10px 12px;
            grid-column: 1 / -1;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        }

        .filters-primary {
            align-items: end;
            width: 100%;
        }

        .filters-principal {
            display: grid;
            gap: 4px;
        }

        .filters-principal__controls {
            display: flex;
            align-items: end;
            gap: 8px;
        }

        .filters-principal__controls select {
            min-width: 220px;
            flex: 1 1 auto;
        }

        .filters-primary:has(.filters-query):has(.filters-principal) {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .filters-primary:has(.filters-query):not(:has(.filters-principal)) {
            grid-template-columns: minmax(260px, 420px) auto;
        }

        .filters-primary:has(.filters-query) .filters-principal {
            min-width: 0;
        }

        .filters-primary:has(.filters-query) .filters-query {
            min-width: 0;
            width: min(100%, 420px);
            justify-self: start;
        }

        .filters-primary:has(.filters-query) .filters-query input {
            width: 100%;
        }

        .filters-primary:has(.filters-query) .filters-actions {
            justify-self: end;
        }

        .filters label { gap: 4px; }
        .filters [data-product-filter-status]:empty { display: none; }

        label {
            display: grid;
            gap: 6px;
            font-size: 13px;
            color: var(--muted);
        }

        input,
        select,
        textarea {
            width: 100%;
            min-height: 44px;
            padding: 11px 12px;
            border: 1px solid var(--ui-border-strong);
            border-radius: 12px;
            background: var(--ui-surface);
            color: var(--ink);
            transition: border-color 0.16s ease, box-shadow 0.16s ease, background-color 0.16s ease;
        }

        textarea {
            min-height: 112px;
            line-height: 1.5;
            resize: vertical;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--ui-border-strong);
            box-shadow: var(--shadow-card);
        }

        .filters .actions {
            display: flex;
            align-items: end;
            gap: 12px;
            flex-wrap: wrap;
        }

        .filters-actions {
            justify-self: end;
        }

        .product-filters-toggle {
            display: none;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            width: 100%;
            min-height: 44px;
            padding: 10px 14px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: var(--surface);
            color: var(--ink);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.01em;
            cursor: pointer;
            box-shadow: var(--shadow-soft);
        }

        .product-filters-toggle__icon {
            font-size: 18px;
            line-height: 1;
            transition: transform 0.18s ease;
        }

        .product-filters-toggle[aria-expanded="true"] .product-filters-toggle__icon {
            transform: rotate(180deg);
        }

        @media (max-width: 860px) {
            .product-filters-toggle {
                display: inline-flex;
            }

            [data-product-filters-panel][data-collapsed="true"] {
                display: none;
            }
        }

        @media (min-width: 861px) {
            .product-filters-toggle {
                display: none !important;
            }

            [data-product-filters-panel] {
                display: block !important;
            }
        }

        .table-shell {
            overflow: hidden;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--ui-surface);
        }

        th,
        td {
            padding: 15px 18px;
            border-top: 1px solid var(--line);
            text-align: left;
            vertical-align: top;
        }

        th {
            font-size: 12px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
            background: var(--ui-surface);
            border-top: none;
        }

        tbody tr:not(.dependency-row):hover td {
            background: var(--product-primary-soft);
        }

        .mono {
            font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
        }

        .capsule {
            display: inline-flex;
            width: fit-content;
            padding: 6px 10px;
            margin: 2px 6px 2px 0;
            font-size: 11px;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: var(--ui-surface);
        }

        .capsule.dark {
            color: var(--product-primary-text);
            background: var(--ink);
        }

        .capsule.accent {
            color: var(--accent);
            background: var(--accent-soft);
        }

        .capsule.warn {
            color: var(--warn);
            background: var(--warn-soft);
        }

        .pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 18px 16px 14px;
            margin: 26px 16px 16px;
            border-top: 1px solid var(--line);
            border-radius: 16px;
            background: var(--surface);
            box-shadow: var(--shadow-soft);
        }

        .pagination-summary {
            font-size: 0.96rem;
            line-height: 1.45;
            color: var(--muted);
        }

        .pagination-links {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .pagination a,
        .pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 38px;
            padding: 8px 12px;
            text-decoration: none;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: var(--ui-surface);
            color: var(--ink);
        }

        .pagination a:hover {
            background: var(--ui-surface);
            border-color: var(--ui-border-strong);
        }

        .pagination span.disabled {
            opacity: 0.45;
        }

        .empty-state,
        .helper {
            padding: 20px;
            background: var(--surface);
            border-radius: var(--radius);
            box-shadow: var(--shadow-soft);
        }

        .flash-stack {
            display: grid;
            gap: 10px;
            margin-top: 16px;
        }

        .flash {
            padding: 16px 18px;
        }

        .flash.success {
            border-color: var(--ui-border-strong);
            background: var(--product-primary-soft);
        }

        .flash.error {
            border-color: var(--ui-border-strong);
            background: var(--ui-warning-bg);
        }

        .flash ul {
            margin: 8px 0 0;
            padding-left: 18px;
        }

        .form-shell {
            display: grid;
            gap: 18px;
            padding: 22px;
        }

        .form-section {
            display: grid;
            gap: 16px;
            padding: 18px;
            border: 1px solid var(--line);
            border-radius: calc(var(--radius) - 2px);
            background: var(--surface-strong);
            box-shadow: var(--shadow-soft);
        }

        .form-section__copy {
            max-width: 64ch;
            color: var(--muted);
            line-height: 1.55;
        }

        .field-grid {
            display: grid;
            gap: 12px 14px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .field-stack {
            display: grid;
            gap: 10px;
            align-content: start;
        }

        .field-note {
            font-size: 12px;
            color: var(--muted);
        }

        .inline-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .inline-actions form {
            margin: 0;
        }

        body.mobile-menu-open {
            overflow: hidden;
        }

        .mobile-menu {
            position: fixed;
            inset: 0;
            z-index: 80;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
        }

        .mobile-menu.is-open {
            opacity: 1;
            pointer-events: auto;
        }

        .mobile-menu-backdrop {
            position: absolute;
            inset: 0;
            background: var(--ui-overlay);
            backdrop-filter: blur(5px);
        }

        .mobile-menu-panel {
            position: absolute;
            top: 12px;
            bottom: 12px;
            left: 12px;
            width: min(86vw, 340px);
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 12px;
            border: 1px solid var(--line);
            border-radius: 28px;
            background: var(--surface-strong);
            box-shadow: var(--shadow-strong);
            backdrop-filter: blur(14px);
            transform: translateX(-110%);
            transition: transform 0.22s ease;
            overflow: auto;
            -webkit-overflow-scrolling: touch;
        }

        .mobile-menu.is-open .mobile-menu-panel {
            transform: translateX(0);
        }

        .mobile-menu-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .mobile-menu-copy {
            display: grid;
            gap: 6px;
        }

        .mobile-menu-copy strong {
            font-size: 16px;
            font-weight: 700;
            line-height: 1.08;
            letter-spacing: -0.01em;
        }

        .mobile-menu-copy p {
            max-width: 24ch;
            color: var(--muted);
            line-height: 1.45;
        }

        .mobile-menu-user {
            display: flex;
            gap: 6px;
            align-items: baseline;
            min-width: 0;
            padding: 4px 8px;
            font-size: 13px;
        }

        .mobile-menu-user small {
            font-size: 11px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .mobile-menu-user strong {
            font-size: 13px;
            letter-spacing: -0.01em;
            flex-shrink: 0;
        }

        .mobile-menu-user span {
            color: var(--muted);
            line-height: 1.45;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .mobile-menu-nav {
            display: grid;
            gap: 2px;
        }

        .mobile-menu-link {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 12px;
            width: 100%;
            min-height: 44px;
            padding: 0 12px;
            text-decoration: none;
            border: 0;
            border-left: 3px solid transparent;
            border-radius: var(--radius-sm);
            background: transparent;
            color: var(--ink);
            transition: transform 0.16s ease, border-color 0.16s ease, background-color 0.16s ease, color 0.16s ease;
        }

        .mobile-menu-link:hover {
            transform: translateX(2px);
            border-color: var(--line);
            background: var(--ui-surface-muted);
        }

        .mobile-menu-link.active {
            color: var(--ui-text);
            border-left-color: var(--product-primary);
            background: var(--product-primary-soft);
        }

        .mobile-menu-link-label {
            font-size: 15px;
            font-weight: 700;
            letter-spacing: -0.01em;
        }

        .mobile-menu-link .material-symbols-outlined {
            font-size: 20px;
            line-height: 1;
            color: currentColor;
            transition: transform 0.16s ease, color 0.16s ease;
        }

        .mobile-menu-link:hover .material-symbols-outlined {
            transform: translateX(2px);
            color: var(--ink);
        }

        .mobile-menu-footer {
            margin-top: auto;
            padding-top: 6px;
            display: grid;
            gap: 8px;
        }

        .mobile-menu-footer .sidebar-theme-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            min-height: 44px;
            padding: 0 12px;
            color: var(--ui-text);
            background: var(--ui-surface);
            border: 1px solid var(--ui-border);
            border-radius: var(--radius-md);
            font: inherit;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
        }

        .mobile-menu-logout {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            min-height: 44px;
            padding: 0 12px;
            border: 1px solid var(--ui-border);
            border-radius: var(--radius-md);
            background: var(--ui-surface);
            color: var(--ui-text);
            font: inherit;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: 0;
            cursor: pointer;
            box-shadow: none;
            transition: transform 0.16s ease, border-color 0.16s ease, background-color 0.16s ease, box-shadow 0.16s ease;
        }

        .mobile-menu-logout .material-symbols-outlined { color: var(--ui-danger); }

        .mobile-menu-logout:hover {
            transform: translateY(-1px);
            border-color: var(--ui-border-strong);
            background: var(--ui-surface-muted);
            box-shadow: var(--shadow-card);
        }

        @media (max-width: 860px) {
            :root {
                --topbar-sticky-gap: 8px;
            }

            .sticky-stack {
                display: grid;
            }

            .shell {
                width: min(100% - 18px, 1240px);
            }

            .topbar {
                padding: 16px 20px;
                margin-bottom: 0;
            }

            .section-head,
            .pagination {
                display: grid;
            }

            .topbar-main {
                align-items: center;
                justify-content: space-between;
                flex-wrap: nowrap;
                gap: 8px;
            }

            .brand {
                flex: 1 1 auto;
                min-width: 0;
            }

            .topbar-tools {
                width: auto;
                justify-content: flex-end;
            }

            .session-tools {
                width: auto;
                align-items: center;
                justify-content: flex-end;
                gap: 8px;
            }

            .session-user {
                display: none;
            }

            .session-actions {
                width: auto;
                justify-content: flex-end;
                white-space: nowrap;
            }

            body.has-page-quick-actions .shell {
                padding-bottom: 112px;
            }

            .session-logout {
                display: none;
            }

            .session-logout--public {
                display: inline-flex;
                width: auto;
                min-width: 44px;
                padding: 0 12px;
            }

            .session-logout-label {
                display: none;
            }

            button.menu-toggle.theme-toggle {
                display: inline-grid;
                min-width: 44px;
                min-height: 44px;
            }

            .topbar-nav {
                display: none;
            }

            .brand strong {
                font-size: 17px;
            }

            .brand small {
                font-size: 11px;
            }

            .filters,
            .metric,
            .panel,
            .module-card,
            .stat-card,
            .helper,
            .empty-state,
            .form-shell {
                padding: 16px;
            }

            .form-section {
                padding: 16px;
            }

            .filters-primary,
            .filters-advanced {
                gap: 12px;
                grid-template-columns: minmax(0, 1fr);
            }

            .filters-primary:has(.filters-query):has(.filters-principal),
            .filters-primary:has(.filters-query):not(:has(.filters-principal)) {
                grid-template-columns: minmax(0, 1fr) auto;
                align-items: end;
            }

            .filters-primary:has(.filters-query) .filters-principal {
                grid-column: 1 / -1;
            }

            .filters-primary:has(.filters-query) .filters-query {
                grid-column: 1;
                min-width: 0;
            }

            .filters-primary:has(.filters-query) .filters-query input {
                min-width: 0;
            }

            .filters-primary:has(.filters-query) .filters-actions {
                grid-column: 2;
                align-self: end;
                justify-self: end;
                justify-content: flex-end;
                flex-wrap: nowrap;
                gap: 8px;
            }

            .filters-primary:has(.filters-query) .filters-actions .btn {
                min-height: 40px;
                padding: 10px 12px;
                white-space: nowrap;
            }

            .page-quick-actions {
                right: calc(12px + env(safe-area-inset-right, 0px));
                bottom: calc(12px + env(safe-area-inset-bottom, 0px));
            }

            .page-quick-actions-toggle {
                min-width: 48px;
                width: 48px;
                height: 48px;
                padding: 0;
                border-radius: 999px;
            }

            .page-quick-actions-toggle-label,
            .page-quick-actions-label {
                display: none;
            }

            .page-quick-actions-item {
                min-width: 48px;
                width: 48px;
                height: 48px;
                padding: 0;
                border-radius: 999px;
                justify-content: center;
            }

            .page-quick-actions-item--voice.is-listening {
                box-shadow: var(--shadow-card);
            }

            .session-tools,
            .session-user {
                text-align: left;
            }

            .session-user {
                min-width: 0;
            }

            .session-tools label {
                min-width: 0;
            }

            table:not(.dependency-table, .verification-table) {
                display: block;
            }

            table:not(.dependency-table, .verification-table) > thead {
                display: none;
            }

            table:not(.dependency-table, .verification-table) > tbody {
                display: grid;
                gap: 14px;
                padding: 14px;
            }

            table:not(.dependency-table, .verification-table) > tbody > tr {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
                padding: 16px;
                border: 1px solid var(--ui-border);
                border-radius: var(--radius-md);
                background: var(--ui-surface);
                box-shadow: var(--shadow-soft);
            }

            table:not(.dependency-table, .verification-table) > tbody > tr > td {
                display: block;
                min-width: 0;
                padding: 0;
                border: 0;
                font-size: 16px;
                overflow-wrap: anywhere;
            }

            table:not(.dependency-table, .verification-table) > tbody > tr > td::before {
                content: attr(data-label);
                display: block;
                margin-bottom: 4px;
                font-size: 14px;
                color: var(--ui-text-muted);
            }

            table:not(.dependency-table, .verification-table) > tbody > tr > td[data-card-title] {
                grid-column: 1 / -1;
                order: -1;
                color: var(--ui-text);
                font-size: 18px;
                font-weight: 700;
            }

            table:not(.dependency-table, .verification-table) > tbody > tr > td[data-card-title]::before,
            table:not(.dependency-table, .verification-table) > tbody > tr > td[data-card-actions]::before {
                display: none;
            }

            table:not(.dependency-table, .verification-table) > tbody > tr > td[data-card-actions],
            table:not(.dependency-table, .verification-table) > tbody > tr > td[colspan],
            table:not(.dependency-table, .verification-table) > tbody > tr > td[data-label="Descrição"],
            table:not(.dependency-table, .verification-table) > tbody > tr > td[data-label="Observação"],
            table:not(.dependency-table, .verification-table) > tbody > tr > td[data-label="Erro"],
            table:not(.dependency-table, .verification-table) > tbody > tr > td[data-label="Responsável"],
            table:not(.dependency-table, .verification-table) > tbody > tr > td[data-label="Arquivo"],
            table:not(.dependency-table, .verification-table) > tbody > tr > td[data-label="Localização"] {
                grid-column: 1 / -1;
            }

            table:not(.dependency-table, .verification-table) > tbody > tr > td[data-card-actions] {
                border-top: 1px solid var(--ui-border);
                padding-top: 12px;
            }

            table:not(.dependency-table, .verification-table) > tbody > tr > td[data-card-actions] .inline-actions {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
            }

            table:not(.dependency-table, .verification-table) > tbody > tr > td[data-card-actions] .inline-actions > :last-child:nth-child(odd) {
                grid-column: 1 / -1;
            }

            table:not(.dependency-table, .verification-table) > tbody > tr > td[data-card-actions] .btn,
            table:not(.dependency-table, .verification-table) > tbody > tr > td[data-card-actions] select {
                min-height: 44px;
                width: 100%;
            }
        }
    </style>
    @include('partials.theme-toggle-assets')
    @include('partials.design-tokens')
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
        .topbar-nav { display: none; }
        button.menu-toggle.theme-toggle { display: inline-flex; }
        .mobile-menu-panel {
            top: 0; bottom: 0; left: 0;
            border-radius: 0;
            background: var(--surface-strong);
            backdrop-filter: none;
        }
        .mobile-menu-link.active { font-weight: 700; }
        .filters-bar { display: flex; align-items: end; gap: 12px; }
        .filters-search { flex: 1; min-width: 0; display: grid; gap: 4px; }
        .filters-dialog { width: min(560px, calc(100% - 32px)); max-height: calc(100dvh - 32px); overflow: auto; padding: 20px; border: 1px solid var(--ui-border); border-radius: var(--radius-lg); background: var(--ui-surface); color: var(--ui-text); box-shadow: var(--shadow-card); }
        .filters-dialog::backdrop { background: var(--ui-overlay); }
        .filters-dialog-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; }
        .filters-dialog-fields { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 220px), 1fr)); gap: 12px; }
        .filters-dialog-actions { display: flex; justify-content: flex-end; flex-wrap: wrap; gap: 12px; margin-top: 20px; }
        .empty-state p { font-size: 14px; color: var(--ui-text-muted); line-height: inherit; }
        .helper:empty { display: none; }
        .toast-region { position: fixed; z-index: 10000; top: 16px; left: 50%; transform: translateX(-50%); width: min(420px, calc(100% - 32px)); display: grid; gap: 8px; pointer-events: none; }
        .toast { padding: 12px 16px; border: 1px solid var(--ui-border); border-left: 4px solid var(--product-primary); border-radius: var(--radius-md); background: var(--ui-surface); color: var(--ui-text); box-shadow: var(--shadow-card); }
        .toast[data-tipo="sucesso"] { border-left-color: var(--ui-success); }
        .toast[data-tipo="erro"] { border-left-color: var(--ui-danger); }
        @media (max-width: 639px) {
            .section-head p { display: none; }
            button.menu-toggle.theme-toggle { display: grid; place-items: center; width: 44px; height: 44px; padding: 0; border-radius: var(--radius-md); }
            button.menu-toggle.theme-toggle .theme-toggle-icon { font-size: 24px; line-height: 1; }
            .filters-dialog-actions { flex-direction: column; }
            .filters-dialog-actions > .btn { width: 100%; min-height: 44px; height: 44px; padding: 0 12px; }
            .filters-bar { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .filters-search { grid-column: 1 / -1; }
            .filters-dialog { width: calc(100% - 24px); max-height: calc(100dvh - 24px); padding: 16px; }
        }
        @media (min-width: 1024px) {
            .toast-region { left: auto; right: 24px; transform: none; }
            .mobile-menu-user { display: none; }
            .filters-primary:has(.filters-query):has(.filters-principal) .filters-actions { grid-column: 3 / -1; }
            .shell, body.page-operational .shell {
                width: calc(100% - 288px);
                max-width: 1440px;
                margin-left: 264px;
                margin-right: 24px;
            }
            .mobile-menu { inset: 0 auto 0 0; width: 240px; opacity: 1; pointer-events: auto; }
            .mobile-menu-backdrop, .mobile-menu-close, button.menu-toggle.theme-toggle { display: none; }
            .mobile-menu-panel { position: fixed; width: 240px; transform: none; box-shadow: none; }
            .mobile-menu-link { min-height: 40px; }
            body.mobile-menu-open { overflow: auto; }
        }
        @media (prefers-reduced-motion: reduce) {
            .mobile-menu, .mobile-menu-panel { transition: none; }
        }
    </style>
    @php
        $menuIcons = [
            'migration.products.index' => 'inventory_2', 'migration.products.verification' => 'fact_check',
            'migration.labels.*' => 'label', 'migration.churches.*' => 'church',
            'migration.departments.*' => 'meeting_room', 'migration.asset-types.*' => 'category',
            'migration.administrations.*' => 'corporate_fare', 'migration.users.*' => 'group',
            'migration.reports.*' => 'summarize', 'migration.spreadsheets.*' => 'upload_file',
            'migration.audits.*' => 'history', 'migration.configuracoes.*' => 'settings',
        ];
        $currentMenuItem = collect($legacyNavigation ?? [])->first(fn ($item) => request()->routeIs(...$item['active_patterns']));
        if (! $currentMenuItem) {
            $currentMenuItem = collect($legacyNavigation ?? [])->first(function ($item) {
                $pattern = $item['active_patterns'][0] ?? '';
                $prefix = preg_replace('/\.[^.]+$/', '.*', $pattern);
                return $prefix && request()->routeIs($prefix);
            });
        }
        $pageTitle = trim(explode('|', $__env->yieldContent('title'))[0]);
        $menuPattern = $currentMenuItem['active_patterns'][0] ?? '';
        $isMenuPage = $menuPattern && (str_contains($menuPattern, '*')
            ? str_ends_with(request()->route()?->getName() ?? '', '.index')
            : request()->routeIs($menuPattern));
        $sectionTitle = $currentMenuItem && $isMenuPage
            ? $currentMenuItem['label'] : ($pageTitle ?: ($currentMenuItem['label'] ?? 'Início'));
        $sectionIcon = $menuIcons[$currentMenuItem['active_patterns'][0] ?? ''] ?? 'dashboard';
        $hideHeroOnOperationalRoutes = request()->routeIs(
            'migration.products.*',
            'migration.asset-types.*',
            'migration.churches.*',
            'migration.departments.*',
            'migration.users.*',
            'migration.reports.*',
            'migration.audits.*',
            'migration.spreadsheets.*',
            'migration.administrations.*',
        ) && ! request()->routeIs(
            'migration.reports.show',
            'migration.reports.changes',
            'migration.spreadsheets.errors',
        );
    @endphp
</head>
<body
    class="@yield('bodyClass') {{ $hideHeroOnOperationalRoutes ? 'page-operational' : '' }}"
    data-route-name="{{ request()->route()?->getName() ?? '' }}"
    data-ui-user="{{ $legacySessionUser['email'] ?? $legacySessionUser['nome'] ?? '' }}"
>
    <div id="toast-region" class="toast-region" role="status" aria-live="polite"></div>
    <a class="skip-link" href="#main-content">Ir para o conteúdo principal</a>
    <main class="shell" id="main-content" tabindex="-1">
        <div class="sticky-stack">
        <header class="topbar">
            <div class="topbar-main">
                <div class="brand">
                    <span class="material-symbols-outlined" aria-hidden="true">{{ $sectionIcon }}</span>
                    <strong>{{ $sectionTitle }}</strong>
                </div>
                <div class="topbar-tools">
                    @if (!empty($legacySessionUser) || session('public_acesso'))
                        <div class="session-tools">
                            @if (!empty($legacySessionUser))
                                <div class="session-user">
                                    <strong>{{ $legacySessionUser['nome'] }}</strong>
                                    <small title="{{ $legacySessionUser['email'] }}">{{ $legacySessionUser['email'] }}</small>
                                </div>
                            @endif
                            <div class="session-actions">
                                <button
                                    class="theme-toggle menu-toggle"
                                    type="button"
                                    data-mobile-menu-toggle
                                    aria-controls="mobile-menu"
                                    aria-expanded="false"
                                    aria-label="Abrir menu"
                                    title="Abrir menu"
                                >
                                    <span class="material-symbols-outlined theme-toggle-icon" aria-hidden="true">menu</span>
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </header>
        </div>

        @yield('content')
    </main>
    <div class="page-quick-actions" data-page-quick-actions hidden>
        <div class="page-quick-actions-panel" data-page-quick-actions-panel id="page-quick-actions-panel" hidden>
            <button
                type="button"
                class="page-quick-actions-item is-primary"
                data-page-quick-actions-top
                aria-label="Voltar ao topo"
                title="Voltar ao topo"
            >
                <span class="material-symbols-outlined" aria-hidden="true">vertical_align_top</span>
                <span class="page-quick-actions-label">Topo</span>
            </button>
            <button
                type="button"
                class="page-quick-actions-item"
                data-page-quick-actions-filters
                aria-label="Ir para os filtros"
                title="Ir para os filtros"
            >
                <span class="material-symbols-outlined" aria-hidden="true">filter_alt</span>
                <span class="page-quick-actions-label">Filtros</span>
            </button>
            <button
                type="button"
                class="page-quick-actions-item"
                data-page-quick-actions-filters-pin
                aria-pressed="false"
                aria-label="Fixar filtros nesta tela"
                title="Fixar filtros"
            >
                <span class="material-symbols-outlined" aria-hidden="true">push_pin</span>
                <span class="page-quick-actions-label" data-page-quick-actions-filters-pin-label>Fixar filtros</span>
            </button>
            <button
                type="button"
                class="page-quick-actions-item page-quick-actions-item--voice"
                data-page-quick-actions-voice
                hidden
                aria-pressed="false"
                aria-label="Abrir microfone para buscar por números"
                title="Microfone"
            >
                <span class="material-symbols-outlined" data-page-quick-actions-voice-icon aria-hidden="true">mic</span>
                <span class="page-quick-actions-label" data-page-quick-actions-voice-label>Microfone</span>
            </button>
        </div>
        <button
            type="button"
            class="page-quick-actions-toggle"
            data-page-quick-actions-toggle
            aria-controls="page-quick-actions-panel"
            aria-expanded="false"
            aria-label="Abrir ações rápidas"
            title="Ações rápidas"
        >
            <span class="material-symbols-outlined" aria-hidden="true">more_vert</span>
            <span class="page-quick-actions-toggle-label">Ações</span>
        </button>
    </div>
        <div class="mobile-menu" id="mobile-menu" data-mobile-menu aria-hidden="true">
            <div class="mobile-menu-backdrop" data-mobile-menu-backdrop></div>
            <aside class="mobile-menu-panel app-sidebar" aria-labelledby="mobile-menu-title">
                <div class="mobile-menu-head">
                    <div class="mobile-menu-copy">
                        <strong id="mobile-menu-title">Check Planilha</strong>
                </div>
                    <button
                        type="button"
                        class="theme-toggle mobile-menu-close"
                        data-mobile-menu-close
                        aria-label="Fechar menu"
                        title="Fechar menu"
                    >
                        <span class="material-symbols-outlined theme-toggle-icon" aria-hidden="true">close</span>
                    </button>
                </div>
                @if (!empty($legacySessionUser))
                    <div class="mobile-menu-user">
                        <strong>{{ $legacySessionUser['nome'] }}</strong>
                        <span title="{{ $legacySessionUser['email'] }}">{{ $legacySessionUser['email'] }}</span>
                    </div>
                @endif
                @if (! session('public_acesso') && ! empty($legacyNavigation))
                    <nav class="mobile-menu-nav" aria-label="Navegação principal">
                        @foreach ($legacyNavigation as $item)
                            <a
                                href="{{ $item['route'] }}"
                                class="mobile-menu-link {{ request()->routeIs(...$item['active_patterns']) ? 'active' : '' }}"
                                @if (request()->routeIs(...$item['active_patterns'])) aria-current="page" @endif
                                data-mobile-menu-link
                            >
                                <span class="material-symbols-outlined" aria-hidden="true">{{ $menuIcons[$item['active_patterns'][0] ?? ''] ?? 'circle' }}</span>
                                <span class="mobile-menu-link-label">{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </nav>
                @endif
                @if (session('public_acesso') || !empty($legacySessionUser))
                    <div class="mobile-menu-footer">
                        <button type="button" class="theme-toggle sidebar-theme-toggle" data-theme-toggle aria-pressed="false" aria-label="Alternar tema">
                            <span class="material-symbols-outlined theme-toggle-icon" data-theme-icon aria-hidden="true">dark_mode</span>
                            <span>Tema</span>
                        </button>
                        <form method="POST" action="{{ session('public_acesso') ? route('public.access.logout') : route('migration.logout') }}">
                            @csrf
                            <button class="mobile-menu-logout" type="submit"><span class="material-symbols-outlined" aria-hidden="true">logout</span>{{ session('public_acesso') ? 'Sair do acesso público' : 'Sair' }}</button>
                        </form>
                    </div>
                @endif
            </aside>
        </div>
    <script>
        window.showToast = (mensagem, { tipo = 'info', duracao = 4000 } = {}) => {
            const region = document.getElementById('toast-region');
            if (!region) return;
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.dataset.tipo = tipo;
            toast.textContent = mensagem;
            region.appendChild(toast);
            window.setTimeout(() => toast.remove(), duracao);
        };

        document.addEventListener('click', (event) => {
            const opener = event.target.closest('[data-filter-open]');
            if (opener) {
                const dialog = document.getElementById(opener.getAttribute('aria-controls'));
                if (dialog instanceof HTMLDialogElement && !dialog.open) {
                    dialog.addEventListener('close', () => opener.focus(), { once: true });
                    dialog.showModal();
                }
                return;
            }
            const close = event.target.closest('[data-filter-close]');
            if (close) close.closest('dialog')?.close();
        });

        (() => {
            const menu = document.querySelector('[data-mobile-menu]');
            const toggle = document.querySelector('[data-mobile-menu-toggle]');

            if (!menu || !toggle) {
                return;
            }

            const backdrop = menu.querySelector('[data-mobile-menu-backdrop]');
            const closeButtons = menu.querySelectorAll('[data-mobile-menu-close]');
            const links = menu.querySelectorAll('[data-mobile-menu-link]');
            const panel = menu.querySelector('.mobile-menu-panel');
            const desktop = window.matchMedia('(min-width: 1024px)');

            const syncViewport = () => {
                const isDesktop = desktop.matches;
                menu.classList.remove('is-open');
                document.body.classList.remove('mobile-menu-open');
                menu.setAttribute('aria-hidden', isDesktop ? 'false' : 'true');
                toggle.setAttribute('aria-expanded', 'false');
                toggle.setAttribute('aria-label', 'Abrir menu');
                toggle.title = 'Abrir menu';
                if (isDesktop) {
                    panel.removeAttribute('role');
                    panel.removeAttribute('aria-modal');
                } else {
                    panel.setAttribute('role', 'dialog');
                    panel.setAttribute('aria-modal', 'true');
                }
            };
            desktop.addEventListener('change', syncViewport);
            syncViewport();

            const setMenuState = (open) => {
                if (desktop.matches) return;
                menu.classList.toggle('is-open', open);
                document.body.classList.toggle('mobile-menu-open', open);
                menu.setAttribute('aria-hidden', open ? 'false' : 'true');
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                toggle.setAttribute('aria-label', open ? 'Fechar menu' : 'Abrir menu');
                toggle.title = open ? 'Fechar menu' : 'Abrir menu';

                if (open) {
                    requestAnimationFrame(() => {
                        const focusTarget = menu.querySelector('[data-mobile-menu-close], [data-mobile-menu-link]');
                        if (focusTarget && typeof focusTarget.focus === 'function') {
                            focusTarget.focus({ preventScroll: true });
                        }
                    });
                } else if (typeof toggle.focus === 'function') {
                    toggle.focus({ preventScroll: true });
                }
            };

            const closeMenu = () => {
                setMenuState(false);
            };

            toggle.addEventListener('click', () => {
                setMenuState(!menu.classList.contains('is-open'));
            });

            closeButtons.forEach((button) => {
                button.addEventListener('click', closeMenu);
            });

            if (backdrop) {
                backdrop.addEventListener('click', closeMenu);
            }

            links.forEach((link) => {
                link.addEventListener('click', closeMenu);
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && menu.classList.contains('is-open')) {
                    closeMenu();
                }
            });

            window.addEventListener('resize', () => {
                if (window.innerWidth > 860 && menu.classList.contains('is-open')) {
                    closeMenu();
                }
            });
        })();

        (() => {
            const quickActions = document.querySelector('[data-page-quick-actions]');
            const toggle = document.querySelector('[data-page-quick-actions-toggle]');
            const panel = document.querySelector('[data-page-quick-actions-panel]');
            const topButton = document.querySelector('[data-page-quick-actions-top]');
            const filtersButton = document.querySelector('[data-page-quick-actions-filters]');
            const filtersPinButton = document.querySelector('[data-page-quick-actions-filters-pin]');
            const filtersPinLabel = document.querySelector('[data-page-quick-actions-filters-pin-label]');
            const voiceButton = document.querySelector('[data-page-quick-actions-voice]');
            const voiceIcon = document.querySelector('[data-page-quick-actions-voice-icon]');
            const voiceLabel = document.querySelector('[data-page-quick-actions-voice-label]');
            const filtersAnchor = document.querySelector('[data-sticky-filters]');
            const listingSurface = document.querySelector('.table-shell, .verification-table-shell');
            const verificationRoot = document.querySelector('[data-verification-autosave]');

            if (!quickActions || !toggle || !panel || !topButton || !listingSurface) {
                return;
            }

            quickActions.hidden = false;
            document.body.classList.add('has-page-quick-actions');

            const setOpenState = (open) => {
                quickActions.classList.toggle('is-open', open);
                panel.hidden = !open;
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                toggle.setAttribute('aria-label', open ? 'Fechar ações rápidas' : 'Abrir ações rápidas');
                toggle.title = open ? 'Fechar ações rápidas' : 'Ações rápidas';
            };

            const closeQuickActions = () => setOpenState(false);
            const pinnedFiltersClass = 'is-pinned';
            const storageKey = (() => {
                const routeName = document.body.dataset.routeName || window.location.pathname;
                const userKey = document.body.dataset.uiUser || 'anonymous';
                const normalizedUser = userKey
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                const normalizedRoute = routeName
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');

                return ['check-planilha', 'filters-pin', normalizedUser || 'anonymous', normalizedRoute || 'page'].join(':');
            })();
            let filtersPinSpacer = null;
            let pinnedFiltersWidth = null;
            let pinnedFiltersLeft = null;

            const readPinnedState = () => {
                try {
                    return window.localStorage.getItem(storageKey) === '1';
                } catch (error) {
                    console.error(error);
                    return false;
                }
            };

            const writePinnedState = (pinned) => {
                try {
                    if (pinned) {
                        window.localStorage.setItem(storageKey, '1');
                        return;
                    }

                    window.localStorage.removeItem(storageKey);
                } catch (error) {
                    console.error(error);
                }
            };

            const ensureFiltersPinSpacer = () => {
                if (!filtersAnchor) {
                    return null;
                }

                if (filtersPinSpacer) {
                    return filtersPinSpacer;
                }

                filtersPinSpacer = document.createElement('div');
                filtersPinSpacer.setAttribute('aria-hidden', 'true');
                filtersPinSpacer.style.display = 'none';
                filtersAnchor.parentNode?.insertBefore(filtersPinSpacer, filtersAnchor);

                return filtersPinSpacer;
            };

            const removeFiltersPinSpacer = () => {
                if (!filtersPinSpacer) {
                    return;
                }

                filtersPinSpacer.remove();
                filtersPinSpacer = null;
            };

            const getTopGap = () => {
                const rawGap = window.getComputedStyle(document.documentElement).getPropertyValue('--topbar-sticky-gap').trim();

                return rawGap !== '' ? rawGap : '12px';
            };

            const syncPinnedFiltersPosition = () => {
                if (!filtersAnchor || !filtersAnchor.classList.contains(pinnedFiltersClass)) {
                    return;
                }

                const rect = filtersAnchor.getBoundingClientRect();
                pinnedFiltersWidth = `${Math.round(rect.width)}px`;
                pinnedFiltersLeft = `${Math.round(rect.left)}px`;

                filtersAnchor.style.position = 'fixed';
                filtersAnchor.style.top = getTopGap();
                filtersAnchor.style.left = pinnedFiltersLeft;
                filtersAnchor.style.width = pinnedFiltersWidth;
                filtersAnchor.style.maxWidth = pinnedFiltersWidth;
                filtersAnchor.style.zIndex = '58';
            };

            const applyPinnedState = (pinned) => {
                if (!filtersAnchor || !filtersPinButton) {
                    return;
                }

                const spacer = ensureFiltersPinSpacer();

                filtersAnchor.classList.toggle(pinnedFiltersClass, pinned);
                filtersPinButton.classList.toggle('is-pinned', pinned);
                filtersPinButton.setAttribute('aria-pressed', pinned ? 'true' : 'false');
                filtersPinButton.setAttribute(
                    'aria-label',
                    pinned ? 'Desafixar filtros desta tela' : 'Fixar filtros nesta tela'
                );
                filtersPinButton.title = pinned ? 'Desafixar filtros' : 'Fixar filtros';

                if (filtersPinLabel instanceof HTMLElement) {
                    filtersPinLabel.textContent = pinned ? 'Desafixar filtros' : 'Fixar filtros';
                }

                if (pinned) {
                    if (spacer) {
                        const rect = filtersAnchor.getBoundingClientRect();
                        spacer.style.display = 'block';
                        spacer.style.height = `${Math.round(rect.height)}px`;
                    }

                    syncPinnedFiltersPosition();
                    return;
                }

                filtersAnchor.style.position = '';
                filtersAnchor.style.top = '';
                filtersAnchor.style.left = '';
                filtersAnchor.style.width = '';
                filtersAnchor.style.maxWidth = '';
                filtersAnchor.style.zIndex = '';

                if (spacer) {
                    spacer.style.display = 'none';
                    spacer.style.height = '';
                }
            };

            const searchInput = verificationRoot?.querySelector('input[name="busca"]');
            const searchForm = searchInput?.closest('form');
            const searchSubmit = searchForm?.querySelector('.filters-actions .btn.primary[type="submit"]');
            const speechRecognitionFactory = window.SpeechRecognition || window.webkitSpeechRecognition;
            const mobileVoiceQuery = window.matchMedia('(max-width: 860px)');

            const normalizeVoiceDigits = (transcript) => {
                const normalized = transcript
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '');
                const tokens = normalized.match(/\d+|[a-z]+/g) || [];
                const digitMap = {
                    zero: '0',
                    um: '1',
                    uma: '1',
                    dois: '2',
                    duas: '2',
                    tres: '3',
                    quatro: '4',
                    cinco: '5',
                    seis: '6',
                    sete: '7',
                    oito: '8',
                    nove: '9',
                };

                return tokens.reduce((accumulator, token) => {
                    if (/^\d+$/.test(token)) {
                        return `${accumulator}${token}`;
                    }

                    if (Object.prototype.hasOwnProperty.call(digitMap, token)) {
                        return `${accumulator}${digitMap[token]}`;
                    }

                    return accumulator;
                }, '');
            };

            let recognition = null;
            let isListening = false;
            let cancelRequested = false;
            let pendingDigits = '';

            const updateVoiceButton = (listening) => {
                if (!voiceButton) {
                    return;
                }

                voiceButton.classList.toggle('is-listening', listening);
                voiceButton.setAttribute('aria-pressed', listening ? 'true' : 'false');
                voiceButton.setAttribute(
                    'aria-label',
                    listening
                        ? 'Cancelar reconhecimento de voz'
                        : 'Abrir microfone para buscar por números'
                );
                voiceButton.title = listening ? 'Cancelar' : 'Microfone';

                if (voiceIcon instanceof HTMLElement) {
                    voiceIcon.textContent = listening ? 'close' : 'mic';
                }

                if (voiceLabel instanceof HTMLElement) {
                    voiceLabel.textContent = listening ? 'Cancelar' : 'Microfone';
                }
            };

            const submitVoiceSearch = (digits) => {
                if (!searchInput || !searchForm || digits === '') {
                    return;
                }

                searchInput.value = digits;
                searchInput.dispatchEvent(new Event('input', { bubbles: true }));
                searchInput.focus({ preventScroll: true });

                window.requestAnimationFrame(() => {
                    if (searchSubmit instanceof HTMLButtonElement || searchSubmit instanceof HTMLInputElement) {
                        searchSubmit.click();
                        return;
                    }

                    if (typeof searchForm.requestSubmit === 'function') {
                        searchForm.requestSubmit();
                        return;
                    }

                    searchForm.submit();
                });
            };

            const syncVoiceAvailability = () => {
                if (!voiceButton) {
                    return;
                }

                const shouldShow = Boolean(
                    verificationRoot
                    && searchInput
                    && searchForm
                    && speechRecognitionFactory
                    && mobileVoiceQuery.matches
                );

                voiceButton.hidden = !shouldShow;

                if (!shouldShow && isListening && recognition) {
                    cancelRequested = true;
                    pendingDigits = '';
                    try {
                        recognition.abort();
                    } catch (error) {
                        console.error(error);
                    }
                }
            };

            const finishListening = () => {
                if (!isListening) {
                    return;
                }

                isListening = false;
                updateVoiceButton(false);
            };

            const stopListening = (cancel = false) => {
                cancelRequested = cancel;

                if (!recognition) {
                    pendingDigits = '';
                    finishListening();
                    return;
                }

                try {
                    cancel ? recognition.abort() : recognition.stop();
                } catch (error) {
                    console.error(error);
                    finishListening();
                }
            };

            const attachRecognition = () => {
                if (!speechRecognitionFactory) {
                    return false;
                }

                recognition = new speechRecognitionFactory();
                recognition.lang = 'pt-BR';
                recognition.interimResults = false;
                recognition.continuous = false;
                recognition.maxAlternatives = 1;

                recognition.onresult = (event) => {
                    let transcript = '';

                    Array.from(event.results).forEach((result) => {
                        if (result.isFinal && result[0]?.transcript) {
                            transcript += `${result[0].transcript} `;
                        }
                    });

                    const digits = normalizeVoiceDigits(transcript);
                    if (digits !== '') {
                        pendingDigits = digits;
                        cancelRequested = false;

                        try {
                            recognition.stop();
                        } catch (error) {
                            console.error(error);
                        }
                    }
                };

                recognition.onerror = (event) => {
                    if (event.error !== 'aborted') {
                        console.error(event);
                    }

                    cancelRequested = true;
                    pendingDigits = '';
                    finishListening();
                };

                recognition.onend = () => {
                    const shouldSubmit = !cancelRequested && pendingDigits !== '';
                    const digits = pendingDigits;

                    pendingDigits = '';
                    finishListening();

                    if (shouldSubmit) {
                        submitVoiceSearch(digits);
                    }

                    cancelRequested = false;
                };

                return true;
            };

            const startListening = () => {
                if (!mobileVoiceQuery.matches || !speechRecognitionFactory || !voiceButton || !searchInput || !searchForm) {
                    return;
                }

                if (!recognition && !attachRecognition()) {
                    return;
                }

                pendingDigits = '';
                cancelRequested = false;

                try {
                    recognition.start();
                    isListening = true;
                    updateVoiceButton(true);
                } catch (error) {
                    console.error(error);
                    finishListening();
                }
            };

            syncVoiceAvailability();
            if (typeof mobileVoiceQuery.addEventListener === 'function') {
                mobileVoiceQuery.addEventListener('change', syncVoiceAvailability);
            } else if (typeof mobileVoiceQuery.addListener === 'function') {
                mobileVoiceQuery.addListener(syncVoiceAvailability);
            }

            if (!filtersAnchor || !filtersButton || !filtersPinButton) {
                filtersButton?.setAttribute('hidden', '');
                filtersPinButton?.setAttribute('hidden', '');
            } else {
                applyPinnedState(readPinnedState());

                filtersButton.addEventListener('click', () => {
                    filtersAnchor.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    closeQuickActions();
                });

                filtersPinButton.addEventListener('click', () => {
                    const pinned = !filtersAnchor.classList.contains('is-pinned');
                    applyPinnedState(pinned);
                    writePinnedState(pinned);
                    filtersAnchor.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    closeQuickActions();
                });

                window.addEventListener('scroll', () => {
                    if (filtersAnchor.classList.contains('is-pinned')) {
                        syncPinnedFiltersPosition();
                    }
                }, { passive: true });

                window.addEventListener('resize', () => {
                    if (filtersAnchor.classList.contains('is-pinned')) {
                        syncPinnedFiltersPosition();
                    }
                });
            }

            if (voiceButton) {
                voiceButton.addEventListener('click', () => {
                    if (isListening) {
                        stopListening(true);
                        return;
                    }

                    startListening();
                });
            }

            toggle.addEventListener('click', () => {
                setOpenState(!quickActions.classList.contains('is-open'));
            });

            topButton.addEventListener('click', () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
                closeQuickActions();
            });

            document.addEventListener('click', (event) => {
                if (!quickActions.classList.contains('is-open')) {
                    return;
                }

                if (quickActions.contains(event.target)) {
                    return;
                }

                closeQuickActions();
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeQuickActions();
                }
            });

            updateVoiceButton(false);

            document.addEventListener('submit', (event) => {
                const form = event.target;
                const message = form instanceof HTMLFormElement ? form.dataset.confirm : '';

                if (message && !window.confirm(message)) {
                    event.preventDefault();
                }
            });

            // Product filters collapsible on mobile (≤860px)
            (() => {
                const panels = Array.from(document.querySelectorAll('[data-product-filters-panel]'));
                const toggles = Array.from(document.querySelectorAll('[data-product-filters-toggle]'));
                if (panels.length === 0 || toggles.length === 0) return;
                const mq = window.matchMedia('(max-width: 860px)');
                const labelFor = (count) => count > 0 ? `Filtros · ${count} ${count === 1 ? 'ativo' : 'ativos'}` : 'Filtros';
                const sync = (collapsed) => {
                    panels.forEach((p) => { p.dataset.collapsed = collapsed ? 'true' : 'false'; });
                    toggles.forEach((t) => {
                        const n = parseInt(t.getAttribute('data-active-count') || '0', 10);
                        t.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                        const lab = t.querySelector('[data-product-filters-toggle-label]');
                        if (lab) lab.textContent = labelFor(isNaN(n) ? 0 : n);
                    });
                };
                const applyBreakpoint = () => {
                    if (mq.matches) {
                        // mobile: collapsed by default
                        const anyExpanded = panels.some((p) => p.dataset.collapsed === 'false');
                        if (!anyExpanded && !panels.some((p) => 'collapsed' in p.dataset)) sync(true);
                        else sync(panels[0]?.dataset.collapsed === 'true');
                    } else {
                        sync(false);
                    }
                };
                // init collapsed on mobile
                if (mq.matches) sync(true);
                else sync(false);
                toggles.forEach((t) => t.addEventListener('click', () => {
                    const isCollapsed = panels[0]?.dataset.collapsed === 'true';
                    sync(!isCollapsed);
                    if (!isCollapsed) return;
                    // when expanding, bring filters into view
                    const anchor = document.querySelector('[data-sticky-filters]');
                    if (anchor) anchor.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }));
                const onChange = () => {
                    if (mq.matches) {
                        // keep current collapsed state on mobile resize within breakpoint
                    } else {
                        sync(false);
                    }
                };
                if (typeof mq.addEventListener === 'function') mq.addEventListener('change', onChange);
                else if (typeof mq.addListener === 'function') mq.addListener(onChange);
            })();
        })();
    </script>
    <script src="{{ asset('assets/forms/input-mask.js') }}?v={{ filemtime(public_path('assets/forms/input-mask.js')) }}"></script>
    <script src="{{ asset('assets/forms/localidades.js') }}?v={{ filemtime(public_path('assets/forms/localidades.js')) }}"></script>
    @include('partials.request-loading')
</body>
</html>
