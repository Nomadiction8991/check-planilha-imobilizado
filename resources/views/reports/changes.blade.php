@extends('layouts.migration')

@section('title', 'Histórico de Alterações | ' . config('app.name'))

@php
    $filters = $history['filtros'];
    $summary = $history['resumo'];
    $sections = $history['secoes'];

    $sectionMeta = [
        'mostrar_alteracoes' => ['key' => 'alteracoes', 'count' => 'total_alteracoes'],
        'mostrar_pendentes' => ['key' => 'pendentes', 'count' => 'total_pendentes'],
        'mostrar_checados' => ['key' => 'checados', 'count' => 'total_checados'],
        'mostrar_observacao' => ['key' => 'observacao', 'count' => 'total_observacao'],
        'mostrar_checados_observacao' => ['key' => 'checados_observacao', 'count' => 'total_checados_observacao'],
        'mostrar_etiqueta' => ['key' => 'etiqueta', 'count' => 'total_etiqueta'],
        'mostrar_novos' => ['key' => 'novos', 'count' => 'total_novos'],
    ];
@endphp

@section('content')
    <section class="hero">
        <span class="eyebrow">Histórico</span>
        <h1>Histórico de alterações no Laravel.</h1>
        <p class="hero-copy">
            Esta tela consolida os filtros e a impressão do relatório de alterações.
        </p>

        <div class="hero-actions">
            <a class="btn" href="{{ route('migration.reports.index', ['comum_id' => $selectedChurchId]) }}">Voltar para relatórios</a>
            <button class="btn primary js-history-print" type="button">Imprimir</button>
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
        <div class="filters">
            <form method="GET" action="{{ route('migration.reports.changes') }}">
                <div class="filters-primary">
                    <label class="filters-principal">
                        Igreja
                        <select name="comum_id">
                            <option value="">Selecione</option>
                            @foreach ($churches as $church)
                                <option value="{{ $church->id }}" @selected((int) $selectedChurchId === (int) $church->id)>
                                    {{ $church->codigo }} - {{ $church->descricao }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <div class="actions filters-actions">
                        <button class="btn primary" type="submit">Aplicar filtros</button>
                        <a class="btn" href="{{ route('migration.reports.changes', ['comum_id' => $selectedChurchId]) }}">Limpar</a>
                    </div>
                </div>

                <div class="filters-advanced">
                    <label>
                        Dependência
                        <select name="dependencia">
                            <option value="">Todas</option>
                            @foreach ($history['dependencias'] as $dependency)
                                <option value="{{ $dependency['id'] }}" @selected((int) ($filters['dependencia'] ?? 0) === $dependency['id'])>
                                    {{ $dependency['descricao'] }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <div class="report-toggle-grid">
                        <label class="report-toggle">
                            <input type="checkbox" name="mostrar_pendentes" value="1" @checked($filters['mostrar_pendentes'])>
                            <span>Pendentes ({{ $summary['total_pendentes'] }})</span>
                        </label>
                        <label class="report-toggle">
                            <input type="checkbox" name="mostrar_checados" value="1" @checked($filters['mostrar_checados'])>
                            <span>Checados ({{ $summary['total_checados'] }})</span>
                        </label>
                        <label class="report-toggle">
                            <input type="checkbox" name="mostrar_observacao" value="1" @checked($filters['mostrar_observacao'])>
                            <span>Com observação ({{ $summary['total_observacao'] }})</span>
                        </label>
                        <label class="report-toggle">
                            <input type="checkbox" name="mostrar_checados_observacao" value="1" @checked($filters['mostrar_checados_observacao'])>
                            <span>Checados com observação ({{ $summary['total_checados_observacao'] }})</span>
                        </label>
                        <label class="report-toggle">
                            <input type="checkbox" name="mostrar_etiqueta" value="1" @checked($filters['mostrar_etiqueta'])>
                            <span>Para etiquetas ({{ $summary['total_etiqueta'] }})</span>
                        </label>
                        <label class="report-toggle">
                            <input type="checkbox" name="mostrar_alteracoes" value="1" @checked($filters['mostrar_alteracoes'])>
                            <span>Editados ({{ $summary['total_alteracoes'] }})</span>
                        </label>
                        <label class="report-toggle">
                            <input type="checkbox" name="mostrar_novos" value="1" @checked($filters['mostrar_novos'])>
                            <span>Novos ({{ $summary['total_novos'] }})</span>
                        </label>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <style>
        .report-toggle-grid {
            display: grid;
            grid-column: 1 / -1;
            gap: 10px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .report-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            border: 1px solid var(--line);
            border-radius: 16px;
            background: var(--surface);
            box-shadow: var(--shadow-soft);
            color: var(--ink);
        }

        .report-toggle input {
            width: auto;
            margin: 0;
        }

        .stacked-tables {
            display: grid;
            gap: 18px;
        }

        .section-card {
            border: 1px solid var(--line);
            background: var(--surface);
            box-shadow: var(--shadow);
            border-radius: var(--radius);
            overflow: hidden;
        }

        .section-card header {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 18px;
            border-bottom: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.72);
        }

        .section-card h2 {
            font-size: 18px;
        }

        @media print {
            .topbar,
            .hero,
            .filters,
            .hero-actions,
            .js-history-print {
                display: none !important;
            }

            .shell {
                width: 100%;
                padding: 0;
            }

            .section-card,
            .metric,
            .table-shell {
                box-shadow: none !important;
                border-color: #d1d5db !important;
                background: #fff !important;
            }
        }
    </style>

    <section class="section">
        <div class="table-shell">
            <table>
                <thead>
                    <tr>
                        <th>Indicador</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>Total de produtos</td><td>{{ $summary['total_geral'] }}</td></tr>
                    <tr><td>Pendentes</td><td>{{ $summary['total_pendentes'] }}</td></tr>
                    <tr><td>Checados</td><td>{{ $summary['total_checados'] }}</td></tr>
                    <tr><td>Com observação</td><td>{{ $summary['total_observacao'] }}</td></tr>
                    <tr><td>Checados com observação</td><td>{{ $summary['total_checados_observacao'] }}</td></tr>
                    <tr><td>Para impressão de etiquetas</td><td>{{ $summary['total_etiqueta'] }}</td></tr>
                    <tr><td>Editados</td><td>{{ $summary['total_alteracoes'] }}</td></tr>
                    <tr><td>Novos</td><td>{{ $summary['total_novos'] }}</td></tr>
                    <tr><td>Total a ser impresso</td><td>{{ $summary['total_mostrar'] }}</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="section stacked-tables">
        @if ($summary['total_geral'] === 0)
            <div class="helper">Nenhum produto encontrado para a igreja e os filtros selecionados.</div>
        @elseif ($summary['total_mostrar'] === 0)
            <div class="helper">Marque pelo menos uma seção para visualizar o relatório.</div>
        @else
            @foreach ($sectionMeta as $flag => $meta)
                @continue(!$filters[$flag] || $summary[$meta['count']] === 0)

                @php
                    $section = $sections[$meta['key']];
                @endphp

                <article class="section-card">
                    <header>
                        <div>
                            <h2>{{ $section['titulo'] }} ({{ $section['total'] }})</h2>
                            <p class="metric-copy">Gerado em {{ now()->format('d/m/Y H:i:s') }}</p>
                        </div>
                    </header>

                    <div class="table-shell">
                        <table>
                            <thead>
                                @if ($meta['key'] === 'alteracoes')
                                    <tr>
                                        <th>Código</th>
                                        <th>Antigo</th>
                                        <th>Novo</th>
                                    </tr>
                                @elseif ($meta['key'] === 'pendentes')
                                    <tr>
                                        <th>Código</th>
                                        <th>Descrição</th>
                                        <th>Dependência</th>
                                    </tr>
                                @elseif (in_array($meta['key'], ['observacao', 'checados_observacao'], true))
                                    <tr>
                                        <th>Código</th>
                                        <th>Descrição</th>
                                        <th>Observações</th>
                                    </tr>
                                @elseif ($meta['key'] === 'novos')
                                    <tr>
                                        <th>Descrição completa</th>
                                        <th>Quantidade</th>
                                    </tr>
                                @else
                                    <tr>
                                        <th>Código</th>
                                        <th>Descrição</th>
                                    </tr>
                                @endif
                            </thead>
                            <tbody>
                                @foreach ($section['itens'] as $item)
                                    <tr>
                                        @if ($meta['key'] === 'alteracoes')
                                            <td class="mono">{{ $item['codigo'] ?? '-' }}</td>
                                            <td>{{ $item['nome_original'] ?? $item['nome_atual'] ?? '-' }}</td>
                                            <td>{{ $item['nome_atual'] ?? '-' }}</td>
                                        @elseif ($meta['key'] === 'pendentes')
                                            <td class="mono">{{ $item['codigo'] ?? '-' }}</td>
                                            <td>{{ $item['nome_atual'] ?? '-' }}</td>
                                            <td>{{ $item['dependencia'] ?? '-' }}</td>
                                        @elseif (in_array($meta['key'], ['observacao', 'checados_observacao'], true))
                                            <td class="mono">{{ $item['codigo'] ?? '-' }}</td>
                                            <td>{{ $item['nome_atual'] ?? '-' }}</td>
                                            <td>{{ $item['observacoes'] ?? '-' }}</td>
                                        @elseif ($meta['key'] === 'novos')
                                            <td>{{ $item['nome_atual'] ?? '-' }}</td>
                                            <td>{{ $item['quantidade'] ?? 'N/A' }}</td>
                                        @else
                                            <td class="mono">{{ $item['codigo'] ?? '-' }}</td>
                                            <td>{{ $item['nome_atual'] ?? '-' }}</td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </article>
            @endforeach
        @endif
    </section>

    <script>
        (() => {
            document.querySelectorAll('.js-history-print').forEach((button) => {
                button.addEventListener('click', () => window.print());
            });
        })();
    </script>
@endsection
