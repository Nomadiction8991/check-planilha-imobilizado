<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    @include('partials.theme-init')
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
            position: sticky;
            top: 12px;
            z-index: 60;
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
            padding: 9px 14px;
            text-decoration: none;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.58);
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
            min-height: 42px;
            padding: 10px 15px;
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
            position: sticky;
            top: 156px;
            z-index: 55;
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            box-shadow: var(--shadow-soft);
        }

        .filters-sticky-sentinel {
            display: block;
            height: 1px;
            margin-top: -1px;
            pointer-events: none;
            visibility: hidden;
        }

        .filters form {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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

        .field-grid {
            display: grid;
            gap: 12px 14px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 48px;
            padding: 12px 16px;
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
            .shell {
                width: min(100% - 18px, 1240px);
            }

            .topbar {
                top: 8px;
                padding: 16px;
            }

            .section-head,
            .pagination {
                display: grid;
            }

            .topbar-main {
                align-items: center;
                justify-content: space-between;
                gap: 12px;
            }

            .session-tools {
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

            .session-logout {
                display: none;
            }

            button.menu-toggle.theme-toggle {
                display: inline-grid;
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

            .filters {
                top: 96px;
            }

            .filters.is-stuck .filters-advanced {
                display: none;
            }

            .filters.is-stuck .filters-primary {
                grid-template-columns: minmax(0, 1fr) minmax(0, 150px);
                align-items: end;
            }

            .filters.is-stuck .filters-principal {
                grid-column: 1 / -1;
            }

            .filters.is-stuck .filters-query {
                grid-column: 1 / 2;
            }

            .filters.is-stuck .filters-actions {
                grid-column: 2 / 3;
                justify-self: end;
            }

            .filters.is-stuck .filters-actions .btn:not(.primary) {
                display: none;
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

            td {
                border-top: none;
                border-bottom: 1px solid var(--line);
                padding-top: 8px;
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
            [
                'label' => 'Painel',
                'route' => route('migration.dashboard'),
                'active' => request()->routeIs('migration.dashboard'),
            ],
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

        if (!empty($legacyPermissions['reports.view'])) {
            $legacyNavigation[] = [
                'label' => 'Relatórios',
                'route' => route('migration.reports.index'),
                'active' => request()->routeIs('migration.reports.*'),
            ];
        }

        if (!empty($legacyPermissions['audits.view'])) {
            $legacyNavigation[] = [
                'label' => 'Auditoria',
                'route' => route('migration.audits.index'),
                'active' => request()->routeIs('migration.audits.*'),
            ];
        }

        if (!empty($legacyPermissions['spreadsheets.import'])) {
            $legacyNavigation[] = [
                'label' => 'Importação',
                'route' => route('migration.spreadsheets.create'),
                'active' => request()->routeIs('migration.spreadsheets.*'),
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
    @endphp
    <main class="shell">
        <header class="topbar">
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

        @yield('content')
    </main>
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
            const filterCards = Array.from(document.querySelectorAll('.filters'));

            if (!filterCards.length || !('IntersectionObserver' in window) || !window.matchMedia) {
                return;
            }

            const mobileQuery = window.matchMedia('(max-width: 860px)');
            const observers = [];

            const clearStickyState = () => {
                observers.splice(0).forEach((observer) => observer.disconnect());
                document.querySelectorAll('[data-filters-sticky-sentinel]').forEach((sentinel) => sentinel.remove());
                filterCards.forEach((card) => card.classList.remove('is-stuck'));
            };

            const setupStickyState = () => {
                clearStickyState();

                if (!mobileQuery.matches) {
                    return;
                }

                filterCards.forEach((card) => {
                    const sentinel = document.createElement('span');
                    sentinel.className = 'filters-sticky-sentinel';
                    sentinel.dataset.filtersStickySentinel = 'true';
                    sentinel.setAttribute('aria-hidden', 'true');
                    card.parentNode?.insertBefore(sentinel, card);

                    const observer = new IntersectionObserver((entries) => {
                        const [entry] = entries;

                        if (!entry) {
                            return;
                        }

                        card.classList.toggle('is-stuck', !entry.isIntersecting);
                    }, {
                        threshold: 0,
                        rootMargin: '-96px 0px 0px 0px',
                    });

                    observer.observe(sentinel);
                    observers.push(observer);
                });
            };

            setupStickyState();

            if (typeof mobileQuery.addEventListener === 'function') {
                mobileQuery.addEventListener('change', setupStickyState);
            } else if (typeof mobileQuery.addListener === 'function') {
                mobileQuery.addListener(setupStickyState);
            }
        })();
    </script>
    @include('partials.request-loading')
</body>
</html>
