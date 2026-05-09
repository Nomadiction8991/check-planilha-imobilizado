@extends('layouts.migration')

@section('title', 'Relatório ' . $preview['formulario'] . ' | ' . config('app.name'))

@section('content')
    @php
        $churchName = $preview['planilha']['descricao'] ?? 'Nenhuma';
        $churchCity = $preview['planilha']['cidade'] ?? 'Cidade não informada';
        $backgroundStatus = $preview['background_image_url'] !== '' ? 'Fundo carregado' : 'Sem fundo';
    @endphp

    <section class="hero report-preview-hero">
        <div class="report-preview-hero__layout">
            <div class="report-preview-hero__intro">
                <span class="eyebrow">Prévia</span>
                <h1>Relatório {{ $preview['formulario'] }} renderizado no novo app.</h1>
                <p class="hero-copy">
                    A pré-visualização usa os templates e fillers já cadastrados, preservando o layout operacional da seção 14.
                </p>

                <div class="hero-actions">
                    <a class="btn" href="{{ route('migration.reports.index', ['comum_id' => $selectedChurchId]) }}">Voltar para relatórios</a>
                    <a class="btn" href="{{ route('migration.reports.editor', ['formulario' => $preview['formulario'], 'comum_id' => $selectedChurchId]) }}">Editar células</a>
                    <button class="btn primary js-report-print" type="button">Imprimir</button>
                </div>
            </div>

            <aside class="report-preview-hero__summary" aria-label="Resumo da pré-visualização">
                <span class="report-preview-hero__summary-kicker">Resumo técnico</span>
                <strong>Formulário {{ $preview['formulario'] }}</strong>
                <p>{{ $preview['total_paginas'] }} página(s) na pré-visualização.</p>

                <div class="report-preview-hero__chips">
                    <span class="report-preview-chip">{{ $backgroundStatus }}</span>
                    <span class="report-preview-chip">Escala A4</span>
                </div>

                <label class="report-preview-toggle">
                    <input class="js-report-field-debug-toggle" type="checkbox">
                    <span>
                        <strong>Mostrar bordas e nomes</strong>
                        <small>Ativa contorno vermelho de 1px e exibe o nome técnico de cada campo.</small>
                    </span>
                </label>
            </aside>
        </div>
    </section>

    <section class="section">
        <div class="report-preview-context">
            <div class="report-preview-context__copy">
                <span class="report-preview-context__label">Igreja selecionada</span>
                <h2>{{ $churchName }}</h2>
                <p>{{ $churchCity }}</p>
            </div>
            <div class="report-preview-context__meta">
                <span class="report-preview-chip">Formulário {{ $preview['formulario'] }}</span>
                <span class="report-preview-chip">{{ $preview['total_paginas'] }} páginas</span>
            </div>
        </div>
    </section>

    <style>
        {!! $preview['style_content'] !!}

        .report-preview-hero {
            position: relative;
            overflow: hidden;
            gap: 0;
            padding: 26px;
            border: 1px solid var(--line);
            border-radius: 28px;
            background:
                radial-gradient(circle at top right, color-mix(in srgb, var(--accent-soft) 84%, transparent), transparent 40%),
                radial-gradient(circle at bottom left, color-mix(in srgb, var(--warn-soft) 70%, transparent), transparent 32%),
                linear-gradient(180deg, color-mix(in srgb, var(--surface-strong) 92%, transparent), color-mix(in srgb, var(--surface) 94%, transparent));
            box-shadow: var(--shadow-strong);
        }

        .report-preview-hero::after {
            content: '';
            position: absolute;
            top: -44px;
            right: -44px;
            width: 180px;
            height: 180px;
            border-radius: 999px;
            background: radial-gradient(circle, color-mix(in srgb, var(--accent) 18%, transparent) 0%, transparent 72%);
            pointer-events: none;
        }

        .report-preview-hero__layout {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 18px;
            grid-template-columns: minmax(0, 1.45fr) minmax(260px, 0.85fr);
            align-items: stretch;
        }

        .report-preview-hero__intro {
            display: grid;
            gap: 12px;
            align-content: start;
        }

        .report-preview-hero__summary {
            display: grid;
            gap: 12px;
            align-content: start;
            padding: 18px;
            border: 1px solid var(--line);
            border-radius: 24px;
            background: color-mix(in srgb, var(--surface-strong) 84%, transparent);
            box-shadow: var(--shadow-soft);
        }

        .report-preview-hero__summary-kicker,
        .report-preview-context__label {
            font-size: 12px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .report-preview-hero__summary strong {
            font-size: 18px;
            line-height: 1.2;
            letter-spacing: -0.02em;
        }

        .report-preview-hero__summary p,
        .report-preview-context__copy p {
            color: var(--muted);
            line-height: 1.5;
        }

        .report-preview-hero__chips,
        .report-preview-context__meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .report-preview-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 11px;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: var(--surface-soft);
            color: var(--ink);
            font-size: 12px;
            line-height: 1.2;
        }

        .report-preview-toggle {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 14px;
            border: 1px solid var(--line);
            border-radius: 18px;
            background: var(--surface-soft);
            cursor: pointer;
        }

        .report-preview-toggle input {
            flex: 0 0 auto;
            margin-top: 2px;
            accent-color: var(--warn);
        }

        .report-preview-toggle strong {
            display: block;
            font-size: 14px;
            line-height: 1.25;
            letter-spacing: -0.01em;
        }

        .report-preview-toggle small {
            display: block;
            margin-top: 3px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.35;
        }

        .report-preview-context {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 14px;
            padding: 18px 20px;
            border: 1px solid var(--line);
            border-radius: 24px;
            background: color-mix(in srgb, var(--surface-strong) 88%, transparent);
            box-shadow: var(--shadow-soft);
        }

        .report-preview-context__copy {
            display: grid;
            gap: 4px;
        }

        .report-preview-context__copy h2 {
            font-size: 22px;
            line-height: 1.15;
            letter-spacing: -0.02em;
        }

        .report-a4-screen {
            display: grid;
            gap: 20px;
        }

        .report-a4-card,
        .report-a4-preview {
            border: 1px solid rgba(24, 21, 17, 0.12);
            background: rgba(255, 252, 247, 0.92);
            box-shadow: 0 24px 70px rgba(38, 28, 12, 0.12);
        }

        .report-a4-card-header {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            font-size: 12px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #6f6253;
            border-bottom: 1px solid rgba(24, 21, 17, 0.12);
        }

        .report-a4-preview {
            overflow: auto;
            padding: 22px;
        }

        .report-a4-sheet {
            position: relative;
            width: 794px;
            height: 1123px;
            overflow: hidden;
            background: #fff;
            margin: 0 auto;
            --report-source-width: 1654px;
            --report-source-height: 2339px;
            --report-scale: 0.48029;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .report-a4-page {
            position: absolute;
            inset: 0;
            width: var(--report-source-width);
            height: var(--report-source-height);
            transform: scale(var(--report-scale));
            transform-origin: top left;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .report-a4-sheet textarea,
        .report-a4-sheet input {
            pointer-events: none;
            user-select: none;
        }

        .report-a4-print-bg {
            position: absolute;
            inset: 0;
            display: block;
            width: 100%;
            height: 100%;
            object-fit: fill;
            z-index: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .report-a4-page .pixel-root {
            position: relative;
            z-index: 1;
        }

        .report-a4-page .a4 {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .report-a4-debug-overlay {
            position: absolute;
            inset: 0;
            z-index: 2;
            pointer-events: none;
            display: none;
        }

        .report-a4-debug-label {
            position: absolute;
            display: block;
            max-width: calc(100% - 8px);
            padding: 1px 4px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.72);
            color: #c62828;
            font-size: 10px;
            line-height: 1.1;
            letter-spacing: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            box-shadow: 0 0 0 1px rgba(198, 40, 40, 0.14);
        }

        body.report-preview-debug-fields .report-a4-page textarea.field {
            border-color: rgba(198, 40, 40, 0.95) !important;
            box-shadow: inset 0 0 0 1px rgba(198, 40, 40, 0.16);
        }

        body.report-preview-debug-fields .report-a4-debug-overlay {
            display: block;
        }

        @media (max-width: 920px) {
            .report-preview-hero,
            .report-preview-context {
                padding: 20px;
            }

            .report-preview-hero__layout {
                grid-template-columns: 1fr;
            }

            .report-preview-context {
                align-items: start;
                flex-direction: column;
            }
        }

        @media print {
            .topbar,
            .hero,
            .report-preview-hero,
            .report-preview-context,
            .section:not(.report-a4-screen),
            .report-a4-debug-overlay,
            .report-preview-toggle,
            .metrics,
            .hero-actions,
            .js-report-print {
                display: none !important;
            }

            .shell {
                width: 100%;
                padding: 0;
            }

            .section {
                margin: 0;
            }

            .report-a4-card,
            .report-a4-preview {
                border: none !important;
                box-shadow: none !important;
                background: transparent !important;
            }

            .report-a4-preview {
                padding: 0 !important;
                overflow: visible !important;
            }

            .report-a4-card {
                page-break-after: always;
                break-after: page;
            }

            .report-a4-card:last-child {
                page-break-after: auto;
                break-after: auto;
            }

            .report-a4-card-header {
                display: none !important;
            }

            .report-a4-sheet {
                width: 210mm !important;
                height: 297mm !important;
            }

            .report-a4-page,
            .report-a4-sheet,
            .report-a4-print-bg,
            .report-a4-page .a4 {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .report-a4-page .a4 {
                background: transparent !important;
            }

            body.report-preview-debug-fields .report-a4-page textarea.field {
                border-color: transparent !important;
                box-shadow: none !important;
            }
        }
    </style>

    <section class="section report-a4-screen">
        @foreach ($preview['paginas'] as $page)
            <article class="report-a4-card">
                <div class="report-a4-card-header">
                    <span>Página {{ $page['numero'] }} de {{ $preview['total_paginas'] }}</span>
                    <span>Pré-visualização A4</span>
                </div>
                <div class="report-a4-preview">
                    <div class="report-a4-sheet">
                        <div class="report-a4-page">
                            @if ($preview['background_image_url'] !== '')
                                <img class="report-a4-print-bg" src="{{ $preview['background_image_url'] }}" alt="" loading="eager" decoding="async" fetchpriority="high">
                            @endif
                            {!! $page['html'] !!}
                        </div>
                    </div>
                </div>
            </article>
        @endforeach
    </section>

    <script>
        (() => {
            const printButtons = document.querySelectorAll('.js-report-print');
            const reportImages = Array.from(document.querySelectorAll('.report-a4-print-bg'));

            const waitForImage = (img) => {
                if (img.complete && img.naturalWidth > 0) {
                    return Promise.resolve();
                }

                if (typeof img.decode === 'function') {
                    return img.decode().catch(() => new Promise((resolve) => {
                        img.addEventListener('load', resolve, { once: true });
                        img.addEventListener('error', resolve, { once: true });
                    }));
                }

                return new Promise((resolve) => {
                    img.addEventListener('load', resolve, { once: true });
                    img.addEventListener('error', resolve, { once: true });
                });
            };

            const waitForPrintAssets = async () => {
                await Promise.all([
                    ...(document.fonts ? [document.fonts.ready.catch(() => undefined)] : []),
                    ...reportImages.map(waitForImage),
                ]);

                await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));
            };

            printButtons.forEach((button) => {
                button.addEventListener('click', async () => {
                    button.disabled = true;

                    try {
                        await waitForPrintAssets();
                        window.print();
                    } finally {
                        button.disabled = false;
                    }
                });
            });

            const debugToggle = document.querySelector('.js-report-field-debug-toggle');
            const storageKey = 'check-planilha-report-debug-fields';
            const pages = Array.from(document.querySelectorAll('.report-a4-page'));

            if (!debugToggle || pages.length === 0) {
                return;
            }

            const parsePx = (value) => Number.parseFloat(String(value || '0').replace('px', '')) || 0;

            const ensureOverlay = (page) => {
                let overlay = page.querySelector('.report-a4-debug-overlay');

                if (!overlay) {
                    overlay = document.createElement('div');
                    overlay.className = 'report-a4-debug-overlay';
                    overlay.setAttribute('aria-hidden', 'true');
                    page.appendChild(overlay);
                }

                return overlay;
            };

            const renderDebugOverlay = (enabled) => {
                document.body.classList.toggle('report-preview-debug-fields', enabled);

                pages.forEach((page) => {
                    const overlay = ensureOverlay(page);
                    overlay.innerHTML = '';

                    if (!enabled) {
                        return;
                    }

                    page.querySelectorAll('textarea.field').forEach((field) => {
                        const name = field.getAttribute('name') || '';
                        if (name === '') {
                            return;
                        }

                        const left = parsePx(field.style.left);
                        const top = parsePx(field.style.top);
                        const width = parsePx(field.style.width);

                        const label = document.createElement('span');
                        label.className = 'report-a4-debug-label';
                        label.textContent = name;
                        label.style.left = `${Math.max(0, left + 3)}px`;
                        label.style.top = `${Math.max(0, top - 13)}px`;
                        label.style.width = `${Math.max(30, width - 6)}px`;
                        overlay.appendChild(label);
                    });
                });
            };

            let stored = false;
            try {
                stored = localStorage.getItem(storageKey) === '1';
            } catch (error) {
                stored = false;
            }

            debugToggle.checked = stored;
            renderDebugOverlay(stored);

            debugToggle.addEventListener('change', () => {
                const enabled = debugToggle.checked;
                try {
                    localStorage.setItem(storageKey, enabled ? '1' : '0');
                } catch (error) {
                    // Ignora falha de armazenamento local.
                }

                renderDebugOverlay(enabled);
            });
        })();
    </script>
@endsection
