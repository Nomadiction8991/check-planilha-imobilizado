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
            @php
                $reportsActiveCount = 0;
                if ((int) ($selectedAdministrationId ?? 0) > 0) $reportsActiveCount++;
                if (($selectedState ?? '') !== '') $reportsActiveCount++;
                if ((int) ($selectedChurchId ?? 0) > 0) $reportsActiveCount++;
            @endphp
            <button type="button" class="product-filters-toggle" data-product-filters-toggle data-active-count="{{ $reportsActiveCount }}" aria-expanded="false" aria-controls="reports-filters-panel">
                <span data-product-filters-toggle-label>{{ $reportsActiveCount > 0 ? 'Filtros · ' . $reportsActiveCount . ' ' . ($reportsActiveCount === 1 ? 'ativo' : 'ativos') : 'Filtros' }}</span>
                <span class="material-symbols-outlined product-filters-toggle__icon" aria-hidden="true">expand_more</span>
            </button>
            <div id="reports-filters-panel" data-product-filters-panel>
            <form method="GET" action="{{ route('migration.reports.index') }}" data-reports-filter-form data-reports-filter-autosubmit>
                <div class="filters-primary">
                    <label for="reports-admin-search">
                        Buscar administração
                        <input
                            id="reports-admin-search"
                            type="search"
                            placeholder="Digite para filtrar"
                            autocomplete="off"
                            aria-controls="reports-admin-select"
                            data-reports-admin-search
                        >
                    </label>
                    <label class="filters-principal">
                        Administração
                        <select id="reports-admin-select" name="administracao_id" data-reports-admin-select data-reports-server-filter>
                            <option value="">Todas as administrações</option>
                            @foreach ($administrations as $administration)
                                <option value="{{ $administration->id }}" @selected((int) ($selectedAdministrationId ?? 0) === (int) $administration->id)>
                                    #{{ $administration->id }} - {{ $administration->descricao }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <p id="reports-admin-search-status" class="helper" role="status" aria-live="polite" hidden data-reports-admin-status></p>

                    <label class="filters-principal">
                        Estado (UF)
                        <select name="estado" id="reports-estado-select" data-reports-server-filter>
                            <option value="">Todos os estados</option>
                            @foreach ($states as $stateCode => $stateLabel)
                                <option value="{{ $stateCode }}" @selected(($selectedState ?? '') === $stateCode)>
                                    {{ $stateCode }} - {{ $stateLabel }}
                                </option>
                            @endforeach
                        </select>
                    </label>

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
                        <select id="reports-church-select" name="comum_id" data-reports-church-select data-reports-server-filter>
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
                        <span class="helper" role="status" aria-live="polite" data-reports-filter-status></span>
                    </div>
                </div>
            </form>
            <script>
                (() => {
                    const form = document.querySelector('[data-reports-filter-form][data-reports-filter-autosubmit]');
                    if (!form) {
                        return;
                    }

                    const submitButton = form.querySelector('button[type="submit"]');
                    const status = form.querySelector('[data-reports-filter-status]');
                    const serverSelects = form.querySelectorAll('[data-reports-server-filter]');
                    let submitTimer = 0;
                    let lastSignature = new URLSearchParams(new FormData(form)).toString();

                    const getSignature = () => new URLSearchParams(new FormData(form)).toString();
                    const submitReportsIfChanged = () => {
                        submitTimer = 0;
                        const signature = getSignature();
                        if (signature === lastSignature) {
                            return;
                        }

                        lastSignature = signature;
                        form.dataset.reportsFilterSubmitting = 'true';
                        if (status) {
                            status.textContent = 'Atualizando relatórios…';
                        }
                        if (submitButton) {
                            submitButton.disabled = true;
                            submitButton.setAttribute('aria-busy', 'true');
                        }

                        if (typeof form.requestSubmit === 'function') {
                            form.requestSubmit();
                        } else {
                            form.submit();
                        }
                    };
                    const scheduleSubmit = (delay) => {
                        window.clearTimeout(submitTimer);
                        submitTimer = window.setTimeout(submitReportsIfChanged, delay);
                    };

                    form.addEventListener('submit', () => {
                        window.clearTimeout(submitTimer);
                        form.dataset.reportsFilterSubmitting = 'true';
                    });

                    serverSelects.forEach((select) => {
                        select.addEventListener('change', () => scheduleSubmit(80));
                    });
                })();
            </script>
            <script>
                (() => {
                    const adminSearch = document.querySelector('[data-reports-admin-search]');
                    const adminSelect = document.querySelector('[data-reports-admin-select]');
                    const adminStatus = document.querySelector('[data-reports-admin-status]');
                    if (adminSearch && adminSelect && adminStatus) {
                        const adminOptions = Array.from(adminSelect.options);
                        const adminPlaceholder = adminOptions.find((o) => o.value === '');
                        const realAdminOptions = adminOptions.filter((o) => o.value !== '');
                        const applyAdminFilter = () => {
                            const term = adminSearch.value.trim().toLowerCase();
                            let visible = 0;
                            realAdminOptions.forEach((opt) => {
                                const match = term === '' || opt.textContent.toLowerCase().includes(term);
                                opt.hidden = !match;
                                opt.disabled = !match;
                                if (match) visible += 1;
                            });
                            if (adminSelect.value !== '' && adminSelect.options[adminSelect.selectedIndex]?.hidden) {
                                adminSelect.value = '';
                            }
                            if (visible === 0 && term !== '') {
                                adminStatus.textContent = 'Nenhuma administração encontrada para "' + adminSearch.value.trim() + '".';
                                adminStatus.hidden = false;
                                adminSelect.disabled = true;
                            } else {
                                adminStatus.textContent = '';
                                adminStatus.hidden = true;
                                adminSelect.disabled = false;
                                if (adminPlaceholder) { adminPlaceholder.hidden = false; adminPlaceholder.disabled = false; }
                            }
                        };
                        adminSearch.addEventListener('input', applyAdminFilter);
                        adminSearch.addEventListener('search', applyAdminFilter);
                    }

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
