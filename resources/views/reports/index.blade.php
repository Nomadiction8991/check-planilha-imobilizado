@extends('layouts.migration')

@section('title', 'Relatórios | ' . config('app.name'))

@section('content')
    <section class="hero">
        <span class="eyebrow">Relatórios</span>
        <h1>Relatórios 14.x e posição de estoque já navegam no novo app.</h1>
        <p class="hero-copy">
            Selecione uma igreja para listar os formulários, a posição de verificação e os backups disponíveis.
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
        <div class="filters" data-sticky-filters>
            <form method="GET" action="{{ route('migration.reports.index') }}">
                <div class="filters-primary">
                    <label for="reports-church-search">
                        Buscar igreja
                        <input
                            id="reports-church-search"
                            type="search"
                            placeholder="Digite para filtrar"
                            autocomplete="off"
                            aria-controls="reports-church-select"
                            data-reports-church-search
                        >
                    </label>
                    <label class="filters-principal">
                        Igreja
                        <select id="reports-church-select" name="comum_id" data-reports-church-select>
                            <option value="">Selecione</option>
                            @foreach ($churches as $church)
                                <option value="{{ $church->id }}" @selected((int) $selectedChurchId === (int) $church->id)>
                                    {{ $church->codigo }} - {{ $church->descricao }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <p id="reports-church-search-status" class="helper" role="status" aria-live="polite" hidden data-reports-church-status></p>

                    <div class="actions filters-actions">
                        <button class="btn primary" type="submit">Carregar relatórios</button>
                        <a class="btn" href="{{ route('migration.reports.index') }}">Limpar</a>
                    </div>
                </div>
            </form>
            <script>
                (() => {
                    const search = document.querySelector('[data-reports-church-search]');
                    const select = document.querySelector('[data-reports-church-select]');
                    const status = document.querySelector('[data-reports-church-status]');
                    if (!search || !select || !status) return;
                    const options = Array.from(select.options);
                    const placeholder = options.find((o) => o.value === '');
                    const churchOptions = options.filter((o) => o.value !== '');
                    const applyFilter = () => {
                        const term = search.value.trim().toLowerCase();
                        let visible = 0;
                        churchOptions.forEach((opt) => {
                            const match = term === '' || opt.textContent.toLowerCase().includes(term);
                            opt.hidden = !match;
                            opt.disabled = !match;
                            if (match) visible += 1;
                        });
                        if (select.value !== '' && select.options[select.selectedIndex]?.hidden) {
                            select.value = '';
                        }
                        if (visible === 0 && term !== '') {
                            status.textContent = 'Nenhuma igreja encontrada para "' + search.value.trim() + '".';
                            status.hidden = false;
                            select.disabled = true;
                        } else {
                            status.textContent = '';
                            status.hidden = true;
                            select.disabled = false;
                            if (placeholder) { placeholder.hidden = false; placeholder.disabled = false; }
                        }
                    };
                    search.addEventListener('input', applyFilter);
                    search.addEventListener('search', applyFilter);
                })();
            </script>
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
                <div class="empty-state">Escolha uma igreja para liberar a lista de relatórios disponíveis.</div>
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
                                    <a class="btn primary" href="{{ $report['rota'] }}">
                                        {{ $report['codigo'] === 'POS' ? 'Abrir posição' : 'Abrir prévia' }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

    </section>
@endsection
