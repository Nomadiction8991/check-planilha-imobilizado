@extends('layouts.migration')

@section('title', 'Auditoria | ' . config('app.name'))

@section('content')
    <section class="hero">
        <span class="eyebrow">Auditoria</span>
        <h1>Auditoria do sistema</h1>
        <p class="hero-copy">Consulte as ações concluídas com sucesso.</p>
    </section>
    @if (session('status') || $errors->any())
        <div class="flash-stack">
            @if (session('status'))
                <div class="flash {{ session('status_type', 'success') === 'error' ? 'error' : 'success' }}">
                    <strong>{{ session('status') }}</strong>
                </div>
            @endif
            @if ($errors->any())
                <div class="flash error" role="alert">
                    <strong>{{ $errors->first() }}</strong>
                </div>
            @endif
        </div>
    @endif

    <section class="section">
        <div class="filters" data-sticky-filters>
            <form method="GET" action="{{ route('migration.audits.index') }}" data-audits-filter-form data-audits-filter-autosubmit data-filter-autosubmit>
                <div class="filters-bar">
                    <div class="filters-search">

                    <label class="filters-query">
                        Busca geral
                        <input
                            id="audits-search" type="search"
                            name="busca"
                            value="{{ $filters['search'] }}"
                            placeholder="Usuário, ação, descrição, rota ou método"
                            data-audits-search data-filter-search data-filter-server
                        >
                    </label></div>
                    <button type="submit" class="btn primary"><span class="material-symbols-outlined" aria-hidden="true">search</span>Buscar</button>
                    <button type="button" class="btn" data-filter-open aria-haspopup="dialog" aria-controls="filtros-audits"><span class="material-symbols-outlined" aria-hidden="true">tune</span>Filtros</button>
                </div>
                <dialog id="filtros-audits" class="filters-dialog" aria-labelledby="filtros-audits-titulo" data-filter-dialog>
                    <header class="filters-dialog-head"><h2 id="filtros-audits-titulo">Filtros</h2><button type="button" class="btn ghost" data-filter-close aria-label="Fechar filtros"><span class="material-symbols-outlined" aria-hidden="true">close</span></button></header>
                    <div class="filters-dialog-fields">
                        <label for="audits-admin-search">
                        Buscar administração
                        <input
                            id="audits-admin-search"
                            type="search"
                            placeholder="Digite para filtrar"
                            autocomplete="off"
                            aria-controls="audits-admin-select"
                            data-audits-admin-search
                        >
                    </label>

                    <label class="filters-principal">
                        Administração
                        <select id="audits-admin-select" name="administracao_id" data-audits-admin-select data-audits-server-filter>
                            <option value="">Todas as administrações</option>
                            @foreach ($administrations as $administration)
                                <option value="{{ $administration->id }}" @selected((int) ($selectedAdministrationId ?? 0) === (int) $administration->id)>
                                    #{{ $administration->id }} - {{ $administration->descricao }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <p id="audits-admin-search-status" class="helper" role="status" aria-live="polite" hidden data-audits-admin-status></p>

                    <label>
                        Módulo
                        <select name="modulo" data-audits-server-filter>
                            <option value="">Todos</option>
                            @foreach ($modules as $module)
                                <option value="{{ $module }}" @selected($filters['module'] === $module)>{{ $module }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        Data inicial
                        <input type="date" name="data_inicio" value="{{ $filters['date_from'] }}" data-audits-server-filter>
                    </label>

                    <label>
                        Data final
                        <input type="date" name="data_fim" value="{{ $filters['date_to'] }}" data-audits-server-filter>
                    </label>

                    </div>
                    <footer class="filters-dialog-actions">
                        <a class="btn" href="{{ route('migration.audits.index') }}"><span class="material-symbols-outlined" aria-hidden="true">filter_alt_off</span>Limpar</a>
                        <button type="submit" class="btn primary"><span class="material-symbols-outlined" aria-hidden="true">check</span>Aplicar filtros</button>
                    </footer>
                </dialog>
            </form>
            <a
                            class="btn"
                            href="{{ route('migration.audits.export', array_filter([
                                'busca' => $filters['search'],
                                'modulo' => $filters['module'],
                                'data_inicio' => $filters['date_from'],
                                'data_fim' => $filters['date_to'],
                                'administracao_id' => $selectedAdministrationId !== null ? (string) $selectedAdministrationId : '',
                            ], static fn (string $value): bool => $value !== '')) }}"
                        >Exportar CSV</a>
            <span class="helper" role="status" aria-live="polite" data-audits-filter-status></span>
        </div>
    </section>
    <script>
        (() => {
            const form = document.querySelector('[data-audits-filter-form][data-audits-filter-autosubmit]');
            if (!form) {
                return;
            }

            const submitButton = form.querySelector('button[type="submit"]');
            const status = form.querySelector('[data-audits-filter-status]');
            const searchInput = form.querySelector('[data-audits-search]');
            const serverFields = form.querySelectorAll('[data-audits-server-filter]');
            let submitTimer = 0;
            let lastSignature = new URLSearchParams(new FormData(form)).toString();

            const getSignature = () => new URLSearchParams(new FormData(form)).toString();
            const resetPage = () => {
                ['page', 'pagina'].forEach((pageName) => {
                    form.querySelectorAll(`[name="${pageName}"]`).forEach((field) => {
                        field.value = '';
                        field.disabled = true;
                    });
                });
            };
            const submitAuditsIfChanged = () => {
                submitTimer = 0;
                const signature = getSignature();
                if (signature === lastSignature) {
                    return;
                }

                lastSignature = signature;
                resetPage();
                form.dataset.auditsFilterSubmitting = 'true';
                if (status) {
                    status.textContent = 'Atualizando auditoria…';
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
                submitTimer = window.setTimeout(submitAuditsIfChanged, delay);
            };

            form.addEventListener('submit', () => {
                window.clearTimeout(submitTimer);
                form.dataset.auditsFilterSubmitting = 'true';
            });


        })();
    </script>
    <script>
        (() => {
            const adminSearch = document.querySelector('[data-audits-admin-search]');
            const adminSelect = document.querySelector('[data-audits-admin-select]');
            const adminStatus = document.querySelector('[data-audits-admin-status]');
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
        })();
    </script>

    <section class="section" aria-label="Eventos de auditoria">
        <div class="section-head">
            <div>
                <h2>Auditoria do sistema</h2>
                <p>Escopo atual: {{ $scopeLabel }}. Aqui aparecem apenas ações concluídas com sucesso.</p>
            </div>
        </div>

        <div class="table-shell">
            @if ($audits->isEmpty())
                <div class="empty-state">Nenhum evento auditado encontrado para os filtros atuais.</div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Usuário</th>
                            <th>Módulo</th>
                            <th>Ação</th>
                            <th>Descrição</th>
                            <th>Origem</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($audits as $entry)
                            <tr>
                                <td data-label="Data">
                                    <div class="mono">
                                        {{ \Illuminate\Support\Carbon::createFromFormat('Y-m-d H:i:s', $entry->occurredAt)->format('d/m/Y H:i:s') }}
                                    </div>
                                </td>
                                <td data-label="Usuário">
                                    <div>{{ $entry->userName }}</div>
                                    @if ($entry->userEmail)
                                        <div class="table-note">{{ $entry->userEmail }}</div>
                                    @endif
                                    @if ($entry->administrationId !== null)
                                        <div class="table-note">Administração #{{ $entry->administrationId }}</div>
                                    @elseif ($entry->churchId !== null)
                                        <div class="table-note">Igreja #{{ $entry->churchId }}</div>
                                    @endif
                                </td>
                                <td data-label="Módulo">
                                    <span class="capsule dark">{{ $entry->module }}</span>
                                </td>
                                <td data-label="Ação">
                                    {{ $entry->action }}
                                </td>
                                <td data-label="Descrição">
                                    <div>{{ $entry->description }}</div>
                                    @if ($entry->routeName)
                                        <div class="table-note">{{ $entry->routeName }}</div>
                                    @endif
                                </td>
                                <td data-label="Origem">
                                    <div class="inline-actions">
                                        <span class="capsule accent">{{ $entry->method }}</span>
                                        <span class="capsule">{{ $entry->statusCode }}</span>
                                    </div>
                                    @if ($entry->path !== '')
                                        <div class="table-note">{{ $entry->path }}</div>
                                    @endif
                                    @if ($entry->ipAddress)
                                        <div class="table-note">{{ $entry->ipAddress }}</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @include('partials.pagination', ['paginator' => $audits])
            @endif
        </div>
    </section>
    @include('partials.filter-autosubmit')
@endsection
