@extends('layouts.migration')

@section('title', 'Relatório ' . $preview['formulario'] . ' | ' . config('app.name'))

@section('content')
    <section class="hero">
        <span class="eyebrow">Prévia</span>
        <h1>Relatório {{ $preview['formulario'] }} renderizado no novo app.</h1>
        <p class="hero-copy">
            A pré-visualização usa os templates e fillers já cadastrados, preservando o layout operacional da seção 14.
        </p>

        <div class="hero-actions">
            <a class="btn" href="{{ route('migration.reports.index', ['comum_id' => $selectedChurchId]) }}">Voltar para relatórios</a>
            <a class="btn" href="{{ route('migration.reports.changes', ['comum_id' => $selectedChurchId]) }}">Histórico de alterações</a>
            <a class="btn" href="{{ route('migration.reports.editor', ['formulario' => $preview['formulario'], 'comum_id' => $selectedChurchId]) }}">Editar células</a>
            <button class="btn primary js-report-print" type="button">Imprimir</button>
        </div>
    </section>

    <section class="section">
        <div class="section-head">
            <div>
                <h2>{{ $preview['planilha']['descricao'] ?? 'n/a' }}</h2>
                <p>{{ $preview['planilha']['cidade'] ?? 'Cidade não informada' }}</p>
            </div>
        </div>
    </section>

    <style>
        {!! $preview['style_content'] !!}

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

        .report-a4-stage {
            width: fit-content;
            margin: 0 auto;
        }

        .report-a4-sheet {
            position: relative;
            width: 794px;
            min-height: 1123px;
            overflow: hidden;
            background: #fff;
        }

        .report-a4-sheet textarea,
        .report-a4-sheet input {
            pointer-events: none;
            user-select: none;
        }

        .report-a4-print-bg {
            display: none;
        }

        @media print {
            .topbar,
            .hero,
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
                min-height: 297mm !important;
            }

            .report-a4-print-bg {
                display: block !important;
                position: absolute;
                inset: 0;
                width: 100%;
                height: 100%;
                object-fit: cover;
                z-index: 0;
            }

            .pixel-root {
                position: relative;
                z-index: 1;
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
                    <div class="report-a4-stage">
                        <div class="report-a4-sheet">
                            @if ($preview['background_image_url'] !== '')
                                <img class="report-a4-print-bg" src="{{ $preview['background_image_url'] }}" alt="">
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
            document.querySelectorAll('.js-report-print').forEach((button) => {
                button.addEventListener('click', () => window.print());
            });
        })();
    </script>
@endsection
