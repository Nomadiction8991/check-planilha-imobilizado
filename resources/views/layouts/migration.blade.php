<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    @include('partials.theme-init')
    @include('partials.pwa')
    <style>
        :root {
            --bg: #f5f0e8;
            --surface: rgba(255, 252, 247, 0.92);
            --surface-strong: #fffdf8;
            --surface-soft: rgba(255, 255, 255, 0.68);
            --ink: #181511;
            --muted: #6f6253;
            --line: rgba(24, 21, 17, 0.12);
            --accent: #1f6f5f;
            --accent-soft: rgba(31, 111, 95, 0.12);
            --warn: #8b3d19;
            --warn-soft: rgba(139, 61, 25, 0.12);
            --radius-sm: 12px;
            --radius: 18px;
            --radius-lg: 24px;
            --shadow-soft: 0 10px 24px rgba(38, 28, 12, 0.08);
            --shadow: 0 16px 38px rgba(38, 28, 12, 0.08);
            --shadow-strong: 0 24px 60px rgba(38, 28, 12, 0.12);
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
            background:
                radial-gradient(circle at top left, rgba(31, 111, 95, 0.14), transparent 30%),
                radial-gradient(circle at right center, rgba(139, 61, 25, 0.14), transparent 24%),
                linear-gradient(180deg, #f8f3ed 0%, var(--bg) 100%);
        }

        a {
            color: inherit;
        }

        .shell {
            width: min(1240px, calc(100% - 28px));
            margin: 0 auto;
            padding: 28px 0 56px;
        }

        .sticky-stack {
            position: sticky;
            top: var(--topbar-sticky-gap);
            z-index: 70;
            display: grid;
            gap: 12px;
        }

        .sticky-stack-slot {
            display: none;
        }

        .sticky-stack.has-slot .sticky-stack-slot {
            display: grid;
            gap: 12px;
        }

        .sticky-stack.is-stuck .filters {
            padding-block: 16px;
        }

        .sticky-stack.is-stuck .filters .filters-advanced {
            display: none;
        }

        .sticky-stack.is-stuck .filters .filters-actions .btn:not(.primary) {
            display: none;
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
            gap: 14px;
            padding: 18px 20px;
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            box-shadow: var(--shadow-soft);
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
            display: grid;
            gap: 4px;
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
            background: rgba(255, 255, 255, 0.58);
            min-height: 42px;
            align-items: center;
            transition: background-color 0.16s ease, border-color 0.16s ease, color 0.16s ease, transform 0.16s ease;
        }

        .nav a:hover {
            color: var(--ink);
            background: rgba(255, 255, 255, 0.86);
            border-color: rgba(24, 21, 17, 0.18);
            transform: translateY(-1px);
        }

        .nav a.active {
            color: var(--accent);
            background: var(--accent-soft);
            border-color: rgba(31, 111, 95, 0.22);
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
            background: linear-gradient(180deg, var(--surface), var(--surface-soft));
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
            background: linear-gradient(180deg, var(--surface-strong), var(--surface));
            color: var(--ink);
            box-shadow: 0 1px 0 rgba(24, 21, 17, 0.04);
            cursor: pointer;
            transition: background-color 0.16s ease, border-color 0.16s ease, color 0.16s ease, transform 0.16s ease, box-shadow 0.16s ease;
        }

        .page-quick-actions-toggle:hover,
        .page-quick-actions-item:hover {
            transform: translateY(-1px);
            border-color: rgba(24, 21, 17, 0.18);
            box-shadow: 0 8px 20px rgba(38, 28, 12, 0.08);
        }

        .page-quick-actions-toggle {
            min-width: 132px;
        }

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

        .page-quick-actions-item {
            min-width: 132px;
            justify-content: flex-start;
        }

        .page-quick-actions-item.is-primary {
            color: #f9f7f2;
            background: linear-gradient(180deg, var(--ink), #1f1a14);
            border-color: rgba(24, 21, 17, 0.18);
        }

        @media (min-width: 861px) {
            .page-quick-actions-item--voice {
                display: none !important;
            }
        }

        .page-quick-actions-item--voice.is-listening {
            border-color: rgba(31, 111, 95, 0.28);
            background: linear-gradient(180deg, rgba(31, 111, 95, 0.18), rgba(255, 255, 255, 0.92));
            color: var(--accent);
            animation: page-quick-actions-pulse 1.2s ease-in-out infinite;
        }

        .page-quick-actions-item--voice.is-listening .material-symbols-outlined {
            color: var(--accent);
        }

        @keyframes page-quick-actions-pulse {
            0%,
            100% {
                box-shadow:
                    0 1px 0 rgba(24, 21, 17, 0.04),
                    0 10px 22px rgba(31, 111, 95, 0.08);
            }
            50% {
                box-shadow:
                    0 0 0 3px rgba(31, 111, 95, 0.12),
                    0 12px 26px rgba(31, 111, 95, 0.14);
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
            border: 1px solid rgba(139, 61, 25, 0.20);
            border-radius: 999px;
            background: linear-gradient(180deg, rgba(255, 245, 240, 0.92), rgba(245, 228, 221, 0.84));
            color: var(--warn);
            box-shadow:
                0 1px 0 rgba(24, 21, 17, 0.04),
                0 8px 18px rgba(139, 61, 25, 0.08);
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
            outline: 2px solid rgba(31, 111, 95, 0.35);
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
            transition: background-color 0.16s ease, border-color 0.16s ease, color 0.16s ease, transform 0.16s ease, box-shadow 0.16s ease;
            box-shadow: 0 1px 0 rgba(24, 21, 17, 0.04);
            cursor: pointer;
        }

        .btn:hover {
            transform: translateY(-1px);
            border-color: rgba(24, 21, 17, 0.18);
            box-shadow: 0 8px 20px rgba(38, 28, 12, 0.08);
        }

        .btn.primary {
            color: #f9f7f2;
            background: var(--ink);
            border-color: rgba(24, 21, 17, 0.18);
        }

        .btn.primary:hover {
            color: #fffdf8;
            background: #1f1a14;
        }

        .section {
            margin-top: 28px;
        }

        .section-head {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

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
            border: 1px solid rgba(31, 111, 95, 0.18);
            border-radius: 999px;
            color: var(--ink);
            background: rgba(255, 255, 255, 0.74);
        }

        .status {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            padding: 6px 10px;
            border: 1px solid rgba(31, 111, 95, 0.18);
            border-radius: 999px;
            color: var(--accent);
            background: var(--accent-soft);
        }

        .status.warn {
            color: var(--warn);
            border-color: rgba(139, 61, 25, 0.22);
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
            padding: 20px;
            z-index: 55;
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            box-shadow: var(--shadow-soft);
            transition: box-shadow 0.18s ease, transform 0.18s ease, border-color 0.18s ease, background-color 0.18s ease;
        }

        .filters form {
            display: grid;
            gap: 14px;
            grid-template-columns: 1fr;
            width: 100%;
        }

        .filters.is-stuck {
            box-shadow: 0 14px 30px rgba(24, 21, 17, 0.14);
            border-color: rgba(31, 111, 95, 0.16);
        }

        .filters-primary,
        .filters-advanced {
            display: grid;
            gap: 14px;
            grid-column: 1 / -1;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        }

        .filters-primary {
            align-items: end;
            width: 100%;
        }

        .filters-primary:has(.filters-query):has(.filters-principal) {
            grid-template-columns: minmax(220px, 320px) minmax(260px, 420px) auto;
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

        .sticky-stack-slot .filters {
            position: static;
            z-index: auto;
            margin: 0;
        }

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
            border: 1px solid rgba(24, 21, 17, 0.18);
            border-radius: 12px;
            background: #fff;
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
            border-color: rgba(31, 111, 95, 0.55);
            box-shadow: 0 0 0 3px rgba(31, 111, 95, 0.12);
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

        .table-shell {
            overflow: hidden;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.28);
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
            background: rgba(255, 255, 255, 0.72);
            border-top: none;
        }

        tbody tr:hover td {
            background: rgba(31, 111, 95, 0.035);
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
            background: rgba(255, 255, 255, 0.74);
        }

        .capsule.dark {
            color: #f9f7f2;
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
            padding: 14px 16px;
            margin-top: 14px;
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
            background: #fff;
            color: var(--ink);
        }

        .pagination a:hover {
            background: rgba(255, 255, 255, 0.9);
            border-color: rgba(24, 21, 17, 0.18);
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
            border-color: rgba(31, 111, 95, 0.26);
            background: rgba(31, 111, 95, 0.08);
        }

        .flash.error {
            border-color: rgba(139, 61, 25, 0.26);
            background: rgba(139, 61, 25, 0.08);
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
            background: rgba(15, 12, 9, 0.42);
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
            gap: 16px;
            padding: 18px;
            border: 1px solid var(--line);
            border-radius: 28px;
            background: linear-gradient(180deg, var(--surface), var(--surface-soft));
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
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .mobile-menu-copy {
            display: grid;
            gap: 6px;
        }

        .mobile-menu-copy strong {
            font-size: 18px;
            line-height: 1.08;
            letter-spacing: -0.01em;
        }

        .mobile-menu-copy p {
            max-width: 24ch;
            color: var(--muted);
            line-height: 1.45;
        }

        .mobile-menu-user {
            display: grid;
            gap: 4px;
            padding: 14px 16px;
            border: 1px solid var(--line);
            border-radius: 20px;
            background: var(--surface-soft);
        }

        .mobile-menu-user small {
            font-size: 11px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .mobile-menu-user strong {
            font-size: 15px;
            letter-spacing: -0.01em;
        }

        .mobile-menu-user span {
            color: var(--muted);
            line-height: 1.45;
            overflow-wrap: anywhere;
        }

        .mobile-menu-nav {
            display: grid;
            gap: 10px;
        }

        .mobile-menu-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            width: 100%;
            min-height: 52px;
            padding: 14px 16px;
            text-decoration: none;
            border: 1px solid var(--line);
            border-radius: 18px;
            background: var(--surface-soft);
            color: var(--ink);
            transition: transform 0.16s ease, border-color 0.16s ease, background-color 0.16s ease, color 0.16s ease;
        }

        .mobile-menu-link:hover {
            transform: translateX(2px);
            border-color: var(--line);
            background: var(--surface-strong);
        }

        .mobile-menu-link.active {
            color: var(--accent);
            border-color: rgba(31, 111, 95, 0.22);
            background: var(--accent-soft);
        }

        .mobile-menu-link-label {
            font-size: 15px;
            font-weight: 700;
            letter-spacing: -0.01em;
        }

        .mobile-menu-link .material-symbols-outlined {
            font-size: 20px;
            line-height: 1;
            color: var(--muted);
            transition: transform 0.16s ease, color 0.16s ease;
        }

        .mobile-menu-link:hover .material-symbols-outlined {
            transform: translateX(2px);
            color: var(--ink);
        }

        .mobile-menu-footer {
            margin-top: auto;
            padding-top: 6px;
        }

        .mobile-menu-logout {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 52px;
            padding: 14px 16px;
            border: 1px solid rgba(139, 61, 25, 0.22);
            border-radius: 16px;
            background: var(--warn-soft);
            color: var(--warn);
            font: inherit;
            font-weight: 700;
            letter-spacing: 0;
            cursor: pointer;
            box-shadow: 0 1px 0 rgba(24, 21, 17, 0.04);
            transition: transform 0.16s ease, border-color 0.16s ease, background-color 0.16s ease, box-shadow 0.16s ease;
        }

        .mobile-menu-logout:hover {
            transform: translateY(-1px);
            border-color: rgba(139, 61, 25, 0.28);
            background: rgba(139, 61, 25, 0.14);
            box-shadow: 0 8px 18px rgba(139, 61, 25, 0.08);
        }

        @media (max-width: 860px) {
            :root {
                --topbar-sticky-gap: 8px;
            }

            .sticky-stack {
                display: contents;
                position: static;
            }

            .shell {
                width: min(100% - 18px, 1240px);
            }

            .topbar {
                padding: 16px;
                margin-bottom: 12px;
            }

            .section-head,
            .pagination {
                display: grid;
            }

            .topbar-main {
                align-items: flex-start;
                justify-content: flex-start;
                flex-wrap: wrap;
                gap: 12px;
            }

            .brand {
                flex: 1 1 100%;
                min-width: 0;
            }

            .topbar-tools {
                width: 100%;
                justify-content: flex-end;
            }

            .session-tools {
                width: 100%;
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

            .sticky-stack-slot {
                position: sticky;
                top: var(--topbar-sticky-gap);
                z-index: 58;
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
                padding: 18px;
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

            .filters-primary:has(.filters-query) .filters-actions .btn.primary {
                min-width: 44px;
                padding-inline: 12px;
                gap: 0;
                font-size: 0;
                line-height: 0;
                color: transparent;
                justify-content: center;
            }

            .filters-primary:has(.filters-query) .filters-actions .btn.primary::before {
                content: 'filter_alt';
                font-family: 'Material Symbols Outlined';
                font-size: 20px;
                line-height: 1;
                color: var(--ink);
            }

            .sticky-stack-slot .filters {
                position: static;
                z-index: auto;
                margin: 0;
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
                box-shadow:
                    0 0 0 3px rgba(31, 111, 95, 0.12),
                    0 12px 24px rgba(31, 111, 95, 0.14);
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

            table,
            thead,
            tbody,
            th,
            td,
            tr {
                display: block;
            }

            thead {
                display: none;
            }

            tbody {
                display: grid;
                gap: 14px;
                padding: 14px;
            }

            tbody tr {
                padding: 14px;
                border: 1px solid var(--line);
                border-radius: 16px;
                background: var(--surface);
                box-shadow: var(--shadow-soft);
            }

            td {
                min-width: 0;
                padding: 0;
                border: 0;
            }

            td::before {
                content: attr(data-label);
                display: block;
                margin-bottom: 6px;
                font-size: 11px;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                color: var(--muted);
            }
        }
    </style>
    @include('partials.theme-toggle-assets')
    @php
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
<body class="@yield('bodyClass') {{ $hideHeroOnOperationalRoutes ? 'page-operational' : '' }}">
    @php
        $legacyNavigation = [
        ];

        if (!empty($legacyPermissions['products.view'])) {
            $legacyNavigation[] = [
                'label' => 'Produtos',
                'route' => route('migration.products.index'),
                'active' => request()->routeIs('migration.products.index', 'migration.products.create', 'migration.products.edit', 'migration.compat.products.*'),
            ];
        }

        if (!empty($legacyPermissions['products.edit'])) {
            $legacyNavigation[] = [
                'label' => 'Verificação',
                'route' => route('migration.products.verification'),
                'active' => request()->routeIs('migration.products.verification'),
            ];
        }

        if (!empty($legacyPermissions['churches.view'])) {
            $legacyNavigation[] = [
                'label' => 'Igrejas',
                'route' => route('migration.churches.index'),
                'active' => request()->routeIs('migration.churches.*'),
            ];
        }

        if (!empty($legacyPermissions['departments.view'])) {
            $legacyNavigation[] = [
                'label' => 'Dependências',
                'route' => route('migration.departments.index'),
                'active' => request()->routeIs('migration.departments.*'),
            ];
        }

        if (!empty($legacyPermissions['asset-types.view'])) {
            $legacyNavigation[] = [
                'label' => 'Tipos de bem',
                'route' => route('migration.asset-types.index'),
                'active' => request()->routeIs('migration.asset-types.*'),
            ];
        }

        if (!empty($legacyPermissions['administrations.view'])) {
            $legacyNavigation[] = [
                'label' => 'Administrações',
                'route' => route('migration.administrations.index'),
                'active' => request()->routeIs('migration.administrations.*'),
            ];
        }

        if (!empty($legacyPermissions['users.view'])) {
            $legacyNavigation[] = [
                'label' => 'Usuários',
                'route' => route('migration.users.index'),
                'active' => request()->routeIs('migration.users.*'),
            ];
        }

        if (!empty($legacyPermissions['reports.view'])) {
            $legacyNavigation[] = [
                'label' => 'Relatórios',
                'route' => route('migration.reports.index'),
                'active' => request()->routeIs('migration.reports.*'),
            ];
        }

        if (!empty($legacyPermissions['spreadsheets.import'])) {
            $legacyNavigation[] = [
                'label' => 'Importação',
                'route' => route('migration.spreadsheets.create'),
                'active' => request()->routeIs('migration.spreadsheets.*'),
            ];
        }

        if (!empty($legacyPermissions['audits.view'])) {
            $legacyNavigation[] = [
                'label' => 'Auditoria',
                'route' => route('migration.audits.index'),
                'active' => request()->routeIs('migration.audits.*'),
            ];
        }
    @endphp
    <main class="shell">
        <div class="sticky-stack" data-sticky-stack>
        <header class="topbar" data-sticky-stack-topbar>
            <div class="topbar-main">
                <div class="brand">
                    <small>Área restrita</small>
                    <strong>Check Planilha</strong>
                </div>
                <div class="topbar-tools">
                    @if (!empty($legacySessionUser))
                        <div class="session-tools">
                            <div class="session-user">
                                <strong>{{ $legacySessionUser['nome'] }}</strong>
                                <small title="{{ $legacySessionUser['email'] }}">{{ $legacySessionUser['email'] }}</small>
                            </div>
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
                                <form method="POST" action="{{ route('migration.logout') }}">
                                    @csrf
                                    <button class="session-logout" type="submit" aria-label="Sair" title="Sair">
                                        <span class="material-symbols-outlined" aria-hidden="true">logout</span>
                                    </button>
                                </form>
                                @include('partials.theme-toggle')
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            @unless (session('public_acesso'))
            <div class="topbar-nav">
                <nav class="nav" aria-label="Navegação principal">
                    @foreach ($legacyNavigation as $item)
                        <a href="{{ $item['route'] }}" class="{{ $item['active'] ? 'active' : '' }}">{{ $item['label'] }}</a>
                    @endforeach
                </nav>
            </div>
            @endunless
        </header>
        <div class="sticky-stack-slot" data-sticky-stack-slot></div>
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
    @unless (session('public_acesso'))
        <div class="mobile-menu" id="mobile-menu" data-mobile-menu aria-hidden="true">
            <div class="mobile-menu-backdrop" data-mobile-menu-backdrop></div>
            <aside class="mobile-menu-panel" role="dialog" aria-modal="true" aria-labelledby="mobile-menu-title">
                <div class="mobile-menu-head">
                    <div class="mobile-menu-copy">
                        <span class="eyebrow">Menu</span>
                        <strong id="mobile-menu-title">Check Planilha</strong>
                        <p>Acesse cadastros e rotinas administrativas.</p>
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
                        <small>Usuário</small>
                        <strong>{{ $legacySessionUser['nome'] }}</strong>
                        <span>{{ $legacySessionUser['email'] }}</span>
                    </div>
                @endif
                <nav class="mobile-menu-nav" aria-label="Navegação principal">
                    @foreach ($legacyNavigation as $item)
                        <a href="{{ $item['route'] }}" class="mobile-menu-link {{ $item['active'] ? 'active' : '' }}" data-mobile-menu-link>
                            <span class="mobile-menu-link-label">{{ $item['label'] }}</span>
                            <span class="material-symbols-outlined" aria-hidden="true">chevron_right</span>
                        </a>
                    @endforeach
                </nav>
                @if (!empty($legacySessionUser))
                    <div class="mobile-menu-footer">
                        <form method="POST" action="{{ route('migration.logout') }}">
                            @csrf
                            <button class="mobile-menu-logout" type="submit">Sair</button>
                        </form>
                    </div>
                @endif
            </aside>
        </div>
    @endunless
    <script>
        (() => {
            const menu = document.querySelector('[data-mobile-menu]');
            const toggle = document.querySelector('[data-mobile-menu-toggle]');

            if (!menu || !toggle) {
                return;
            }

            const backdrop = menu.querySelector('[data-mobile-menu-backdrop]');
            const closeButtons = menu.querySelectorAll('[data-mobile-menu-close]');
            const links = menu.querySelectorAll('[data-mobile-menu-link]');

            const setMenuState = (open) => {
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
            const stickyStack = document.querySelector('[data-sticky-stack]');
            const stickySlot = document.querySelector('[data-sticky-stack-slot]');
            const stickyTopbar = document.querySelector('[data-sticky-stack-topbar]');
            const stickyFilters = Array.from(document.querySelectorAll('[data-sticky-filters]'));
            const mobileStickyQuery = window.matchMedia('(max-width: 860px)');

            if (!stickyStack || !stickySlot || !stickyFilters.length) {
                return;
            }

            stickyFilters.forEach((filterCard) => {
                const filterSection = filterCard.closest('.section');
                stickySlot.appendChild(filterCard);

                if (filterSection && filterSection.children.length === 0) {
                    filterSection.remove();
                }
            });

            stickyStack.classList.add('has-slot');

            let frameId = null;

            const updateStickyState = () => {
                const stickyGap = Number.parseFloat(
                    getComputedStyle(document.documentElement).getPropertyValue('--topbar-sticky-gap')
                ) || 12;
                const stickyAnchor = mobileStickyQuery.matches && stickyTopbar
                    ? stickyTopbar.getBoundingClientRect().bottom
                    : stickyStack.getBoundingClientRect().top;

                stickyStack.classList.toggle('is-stuck', stickyAnchor <= stickyGap + 1);
            };

            const scheduleUpdate = () => {
                if (frameId !== null) {
                    return;
                }

                frameId = window.requestAnimationFrame(() => {
                    frameId = null;
                    updateStickyState();
                });
            };

            updateStickyState();
            window.addEventListener('scroll', scheduleUpdate, { passive: true });
            window.addEventListener('resize', scheduleUpdate);
            window.addEventListener('load', scheduleUpdate);
        })();

        (() => {
            const quickActions = document.querySelector('[data-page-quick-actions]');
            const toggle = document.querySelector('[data-page-quick-actions-toggle]');
            const panel = document.querySelector('[data-page-quick-actions-panel]');
            const topButton = document.querySelector('[data-page-quick-actions-top]');
            const filtersButton = document.querySelector('[data-page-quick-actions-filters]');
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

            if (!filtersAnchor || !filtersButton) {
                filtersButton?.setAttribute('hidden', '');
            } else {
                filtersButton.addEventListener('click', () => {
                    filtersAnchor.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    closeQuickActions();
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
        })();
    </script>
    <script src="{{ asset('assets/forms/input-mask.js') }}?v={{ filemtime(public_path('assets/forms/input-mask.js')) }}"></script>
    @include('partials.request-loading')
</body>
</html>
