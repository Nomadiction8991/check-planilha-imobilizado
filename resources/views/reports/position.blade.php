@extends('layouts.migration')

@section('title', 'Posição de Estoque | ' . config('app.name'))

@php
    $church = $report['planilha'];
    $summary = $report['resumo'];
    $items = $report['itens'];
    $backupUrl = route('migration.reports.changes.export', ['comum_id' => $selectedChurchId]);

    $basicMetrics = [
        [
            'label' => 'Total de produtos',
            'value' => $summary['total_geral'],
            'hint' => 'Posição completa da igreja selecionada.',
            'tone' => 'accent',
        ],
        [
            'label' => 'Pendentes',
            'value' => $summary['total_pendentes'],
            'hint' => 'Sem checagem, etiqueta, observação ou edição.',
            'tone' => 'muted',
        ],
        [
            'label' => 'Checados',
            'value' => $summary['total_checados'],
            'hint' => 'Itens marcados como conferidos.',
            'tone' => 'success',
        ],
        [
            'label' => 'Com observação',
            'value' => $summary['total_observacao'],
            'hint' => 'Itens com anotação registrada.',
            'tone' => 'warning',
        ],
        [
            'label' => 'Para etiqueta',
            'value' => $summary['total_etiqueta'],
            'hint' => 'Itens prontos para impressão.',
            'tone' => 'warning',
        ],
        [
            'label' => 'Editados',
            'value' => $summary['total_alteracoes'],
            'hint' => 'Itens com alteração de cadastro.',
            'tone' => 'accent',
        ],
        [
            'label' => 'Novos',
            'value' => $summary['total_novos'],
            'hint' => 'Itens criados ou importados recentemente.',
            'tone' => 'muted',
        ],
        [
            'label' => 'Backup CSV',
            'value' => $summary['total_backup'],
            'hint' => 'Linhas incluídas no arquivo de exportação.',
            'tone' => 'success',
        ],
    ];

    $comboMetrics = [
        [
            'label' => 'Checados com observação',
            'value' => $summary['total_checados_observacao'],
            'hint' => 'Conferência com anotação.',
            'tone' => 'success',
        ],
        [
            'label' => 'Checados para etiqueta',
            'value' => $summary['total_checados_etiqueta'],
            'hint' => 'Itens já liberados para impressão.',
            'tone' => 'success',
        ],
        [
            'label' => 'Checados com observação para etiqueta',
            'value' => $summary['total_checados_observacao_etiqueta'],
            'hint' => 'Recorte mais completo da checagem.',
            'tone' => 'success',
        ],
        [
            'label' => 'Editados e checados',
            'value' => $summary['total_editados_checados'],
            'hint' => 'Alteração já conferida.',
            'tone' => 'accent',
        ],
        [
            'label' => 'Editados com observação',
            'value' => $summary['total_editados_observacao'],
            'hint' => 'Alteração acompanhada de observação.',
            'tone' => 'accent',
        ],
        [
            'label' => 'Editados para etiqueta',
            'value' => $summary['total_editados_etiqueta'],
            'hint' => 'Alteração que segue para impressão.',
            'tone' => 'accent',
        ],
        [
            'label' => 'Editados e checados para etiqueta',
            'value' => $summary['total_editados_checados_etiqueta'],
            'hint' => 'Alteração validada e pronta para impressão.',
            'tone' => 'accent',
        ],
        [
            'label' => 'Editados com observação para etiqueta',
            'value' => $summary['total_editados_observacao_etiqueta'],
            'hint' => 'Alteração com nota e fluxo de impressão.',
            'tone' => 'accent',
        ],
        [
            'label' => 'Editados, checados e observação',
            'value' => $summary['total_editados_checados_observacao'],
            'hint' => 'Combinação de revisão e anotação.',
            'tone' => 'accent',
        ],
        [
            'label' => 'Editados, checados, observação e etiqueta',
            'value' => $summary['total_editados_checados_observacao_etiqueta'],
            'hint' => 'Recorte mais completo da posição.',
            'tone' => 'accent',
        ],
    ];
@endphp

@section('content')
    <style>
        .position-hero {
            position: relative;
            overflow: hidden;
            padding: 26px 28px;
            border: 1px solid var(--line);
            border-radius: 28px;
            background:
                radial-gradient(circle at top right, color-mix(in srgb, var(--accent-soft) 88%, transparent), transparent 42%),
                radial-gradient(circle at bottom left, color-mix(in srgb, var(--warn-soft) 72%, transparent), transparent 36%),
                linear-gradient(180deg, color-mix(in srgb, var(--surface-strong) 84%, transparent), var(--surface));
            box-shadow: var(--shadow-soft);
        }

        .position-hero::after {
            content: '';
            position: absolute;
            inset: auto -58px -62px auto;
            width: 210px;
            height: 210px;
            border-radius: 999px;
            background: radial-gradient(circle, color-mix(in srgb, var(--accent) 16%, transparent) 0%, transparent 70%);
            pointer-events: none;
        }

        .position-hero .hero-copy {
            max-width: 70ch;
        }

        .position-hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        .position-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: var(--surface-soft);
            color: var(--ink);
            font-size: 13px;
            line-height: 1.2;
        }

        .position-chip strong {
            font-weight: 600;
        }

        .position-panel {
            display: grid;
            gap: 18px;
            padding: 18px;
            border: 1px solid var(--line);
            border-radius: var(--radius-lg);
            background:
                linear-gradient(180deg, color-mix(in srgb, var(--surface-strong) 86%, transparent), color-mix(in srgb, var(--surface) 94%, transparent));
            box-shadow: var(--shadow);
        }

        .position-panel + .position-panel {
            margin-top: 18px;
        }

        .position-panel__head {
            display: grid;
            gap: 4px;
        }

        .position-panel__head strong {
            font-size: 18px;
            letter-spacing: -0.01em;
        }

        .position-panel__head p {
            color: var(--muted);
            line-height: 1.5;
        }

        .position-metric-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        }

        .position-metric {
            display: grid;
            gap: 8px;
            padding: 16px;
            border: 1px solid var(--line);
            border-radius: 20px;
            background: color-mix(in srgb, var(--surface-strong) 82%, transparent);
            box-shadow: var(--shadow-soft);
        }

        .position-metric strong {
            font-size: 14px;
            font-weight: 600;
            color: var(--muted);
            letter-spacing: -0.01em;
        }

        .position-metric span {
            font-size: 28px;
            font-weight: 700;
            line-height: 1;
            color: var(--ink);
        }

        .position-metric small {
            color: var(--muted);
            line-height: 1.45;
        }

        .position-metric--accent {
            border-color: color-mix(in srgb, var(--accent) 24%, var(--line));
        }

        .position-metric--success {
            border-color: color-mix(in srgb, var(--accent) 20%, var(--line));
        }

        .position-metric--warning {
            border-color: color-mix(in srgb, var(--warn) 24%, var(--line));
        }

        .position-metric--muted {
            border-color: var(--line);
        }

        .position-table-shell table thead th {
            background: color-mix(in srgb, var(--surface-strong) 84%, transparent);
        }

        .position-table-shell tbody td {
            vertical-align: top;
        }

        .position-item {
            display: grid;
            gap: 4px;
        }

        .position-item strong {
            font-size: 15px;
            line-height: 1.35;
        }

        .position-item small {
            color: var(--muted);
            line-height: 1.45;
        }

        .position-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: var(--surface-soft);
            color: var(--ink);
            font-size: 12px;
            line-height: 1.2;
            white-space: nowrap;
        }

        .position-status--accent {
            background: color-mix(in srgb, var(--accent-soft) 82%, transparent);
            border-color: color-mix(in srgb, var(--accent) 24%, var(--line));
        }

        .position-status--success {
            background: color-mix(in srgb, rgba(145, 221, 209, 0.18) 82%, transparent);
            border-color: color-mix(in srgb, var(--accent) 22%, var(--line));
        }

        .position-status--warning {
            background: color-mix(in srgb, var(--warn-soft) 88%, transparent);
            border-color: color-mix(in srgb, var(--warn) 22%, var(--line));
        }

        .position-status--muted {
            background: var(--surface-soft);
            border-color: var(--line);
            color: var(--muted);
        }

        .position-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .position-tag {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: var(--surface-soft);
            color: var(--ink);
            font-size: 12px;
            line-height: 1.2;
        }

        .position-tag--accent {
            background: color-mix(in srgb, var(--accent-soft) 78%, transparent);
            border-color: color-mix(in srgb, var(--accent) 22%, var(--line));
        }

        .position-tag--success {
            background: color-mix(in srgb, rgba(145, 221, 209, 0.14) 82%, transparent);
            border-color: color-mix(in srgb, var(--accent) 18%, var(--line));
        }

        .position-tag--warning {
            background: color-mix(in srgb, var(--warn-soft) 82%, transparent);
            border-color: color-mix(in srgb, var(--warn) 18%, var(--line));
        }

        .position-tag--muted {
            background: var(--surface-soft);
        }

        .position-helper {
            padding: 18px 20px;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            background: var(--surface-soft);
            color: var(--muted);
            line-height: 1.5;
        }

        @media (max-width: 860px) {
            .position-hero {
                padding: 20px;
            }

            .position-panel {
                padding: 16px;
            }

            .position-metric span {
                font-size: 24px;
            }
        }

        @media print {
            .topbar,
            .hero-actions,
            .position-hero {
                display: none !important;
            }

            .shell {
                width: 100%;
                padding: 0;
            }

            .table-shell,
            .position-panel {
                box-shadow: none !important;
                background: #fff !important;
                border-color: #d1d5db !important;
            }
        }
    </style>

    <section class="hero position-hero">
        <span class="eyebrow">Relatório operacional</span>
        <h1>Posição de verificação do estoque.</h1>
        <p class="hero-copy">
            Este relatório consolida os itens ativos da igreja selecionada e permite exportar um CSV de backup com a posição atual.
        </p>

        <div class="position-hero-meta">
            <span class="position-chip">
                <strong>Igreja:</strong> {{ $church['codigo'] ?? '-' }} - {{ $church['descricao'] ?? '-' }}
            </span>
            <span class="position-chip">
                <strong>Local:</strong> {{ $church['cidade'] ?? '-' }} / {{ $church['estado'] ?? '-' }}
            </span>
            <span class="position-chip">
                <strong>Administração:</strong> {{ $church['administracao'] ?? '-' }}
            </span>
        </div>

        <div class="hero-actions">
            <a class="btn" href="{{ route('migration.reports.index', ['comum_id' => $selectedChurchId]) }}">Voltar para relatórios</a>
            <a class="btn primary" href="{{ $backupUrl }}">Baixar backup CSV</a>
        </div>
    </section>

    @if (session('status'))
        <div class="flash-stack">
            <div class="flash {{ session('status_type', 'success') === 'error' ? 'error' : 'success' }}">
                <strong>{{ session('status') }}</strong>
            </div>
        </div>
    @endif

    <section class="section">
        <div class="position-panel">
            <div class="position-panel__head">
                <strong>Estados básicos</strong>
                <p>Resumo da posição atual da igreja selecionada.</p>
            </div>

            <div class="position-metric-grid">
                @foreach ($basicMetrics as $metric)
                    <article class="position-metric position-metric--{{ $metric['tone'] }}">
                        <strong>{{ $metric['label'] }}</strong>
                        <span>{{ $metric['value'] }}</span>
                        <small>{{ $metric['hint'] }}</small>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section">
        <div class="position-panel">
            <div class="position-panel__head">
                <strong>Combinações de conferência e edição</strong>
                <p>Os recortes abaixo ajudam a localizar rapidamente itens checados, editados e prontos para backup.</p>
            </div>

            <div class="position-metric-grid">
                @foreach ($comboMetrics as $metric)
                    <article class="position-metric position-metric--{{ $metric['tone'] }}">
                        <strong>{{ $metric['label'] }}</strong>
                        <span>{{ $metric['value'] }}</span>
                        <small>{{ $metric['hint'] }}</small>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section">
        <div class="position-panel">
            <div class="position-panel__head">
                <strong>Itens da posição</strong>
                <p>Esse quadro detalha o estado de cada produto e serve como base para o backup CSV.</p>
            </div>

            @if ($items === [])
                <div class="position-helper">
                    Nenhum produto ativo foi encontrado para a igreja selecionada.
                </div>
            @else
                <div class="table-shell position-table-shell">
                    <table>
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Descrição</th>
                                <th>Dependência</th>
                                <th>Situação</th>
                                <th>Sinais</th>
                                <th>Observação</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                @php
                                    $statusTone = str_contains($item['status_key'], 'editado')
                                        ? 'accent'
                                        : (str_contains($item['status_key'], 'checado')
                                            ? 'success'
                                            : (str_contains($item['status_key'], 'observacao') || str_contains($item['status_key'], 'etiqueta')
                                                ? 'warning'
                                                : 'muted'));

                                    $signals = [];

                                    if ($item['editado']) {
                                        $signals[] = ['label' => 'Editado', 'tone' => 'accent'];
                                    }

                                    if ($item['novo']) {
                                        $signals[] = ['label' => 'Novo', 'tone' => 'muted'];
                                    }

                                    if ($item['checado']) {
                                        $signals[] = ['label' => 'Checado', 'tone' => 'success'];
                                    }

                                    if ($item['imprimir_etiqueta']) {
                                        $signals[] = ['label' => 'Etiqueta', 'tone' => 'warning'];
                                    }
                                @endphp
                                <tr>
                                    <td class="mono">{{ $item['codigo'] !== '' ? $item['codigo'] : '-' }}</td>
                                    <td>
                                        <div class="position-item">
                                            <strong>{{ $item['nome_atual'] !== '' ? $item['nome_atual'] : '-' }}</strong>
                                            @if ($item['nome_original'] !== '' && $item['nome_original'] !== $item['nome_atual'])
                                                <small>Original: {{ $item['nome_original'] }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ $item['dependencia'] !== '' ? $item['dependencia'] : '-' }}</td>
                                    <td>
                                        <span class="position-status position-status--{{ $statusTone }}">
                                            {{ $item['status_label'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="position-tags">
                                            @if ($signals === [])
                                                <span class="position-tag position-tag--muted">Sem sinais</span>
                                            @else
                                                @foreach ($signals as $signal)
                                                    <span class="position-tag position-tag--{{ $signal['tone'] }}">
                                                        {{ $signal['label'] }}
                                                    </span>
                                                @endforeach
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ $item['observacoes'] !== '' ? $item['observacoes'] : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>

@endsection
