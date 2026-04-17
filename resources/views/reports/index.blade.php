@extends('layouts.migration')

@section('title', 'Relatórios | ' . config('app.name'))

@section('content')
    <section class="hero">
        <span class="eyebrow">Relatórios</span>
        <h1>Relatórios 14.x já navegam no novo app.</h1>
        <p class="hero-copy">
            Selecione uma igreja para listar os formulários e abrir a prévia.
        </p>
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
            <form method="GET" action="{{ route('migration.reports.index') }}">
                <label>
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

                <div class="actions">
                    <button class="btn primary" type="submit">Carregar relatórios</button>
                    <a class="btn" href="{{ route('migration.reports.index') }}">Limpar</a>
                </div>
            </form>
        </div>
    </section>

    <section class="section">
        <div class="section-head">
            <div>
                <h2>Relatórios disponíveis</h2>
                <p>Selecione uma igreja para listar os formulários e abrir a prévia.</p>
            </div>
        </div>

        <div class="table-shell">
            @if (!$selectedChurchId)
                <div class="empty-state">Escolha uma igreja para liberar a lista de relatórios 14.x disponíveis.</div>
            @elseif ($reports === [])
                <div class="empty-state">Não há relatórios disponíveis para a igreja selecionada no momento.</div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descrição</th>
                            <th>Título</th>
                            <th>Itens</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reports as $report)
                            <tr>
                                <td data-label="Código" class="mono">{{ $report['codigo'] }}</td>
                                <td data-label="Descrição">{{ $report['descricao'] }}</td>
                                <td data-label="Título">{{ $report['titulo'] }}</td>
                                <td data-label="Itens">{{ $report['quantidade'] }} item(ns)</td>
                                <td data-label="Ação">
                                    <a class="btn primary" href="{{ $report['rota'] }}">Abrir prévia</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        @if ($selectedChurchId && !empty($legacyPermissions['reports.changes.view']))
            <div class="inline-actions" style="margin-top: 14px;">
                <a class="btn" href="{{ route('migration.reports.changes', ['comum_id' => $selectedChurchId]) }}">Abrir histórico</a>
            </div>
        @endif
    </section>
@endsection
