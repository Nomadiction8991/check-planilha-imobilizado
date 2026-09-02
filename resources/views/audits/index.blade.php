@extends('layouts.migration')

@section('title', 'Auditoria | ' . config('app.name'))

@section('content')
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
        <div class="filters filters--audit" data-sticky-filters>
            <form method="GET" action="{{ route('migration.audits.index') }}">
                <div class="filters-primary">
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
                        <select id="audits-admin-select" name="administracao_id" data-audits-admin-select>
                            <option value="">Todas as administrações</option>
                            @foreach ($administrations as $administration)
                                <option value="{{ $administration->id }}" @selected((int) ($selectedAdministrationId ?? 0) === (int) $administration->id)>
                                    #{{ $administration->id }} - {{ $administration->descricao }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <p id="audits-admin-search-status" class="helper" role="status" aria-live="polite" hidden data-audits-admin-status></p>

                    <label class="filters-query">
                        Busca geral
                        <input
                            type="text"
                            name="busca"
                            value="{{ $filters['search'] }}"
                            placeholder="Usuário, ação, descrição, rota ou método"
                        >
                    </label>

                    <div class="actions filters-actions">
                        <button class="btn primary" type="submit">Filtrar</button>
                        <a class="btn" href="{{ route('migration.audits.index') }}">Limpar</a>
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
                    </div>
                </div>

                <div class="filters-advanced">
                    <label>
                        Módulo
                        <select name="modulo">
                            <option value="">Todos</option>
                            @foreach ($modules as $module)
                                <option value="{{ $module }}" @selected($filters['module'] === $module)>{{ $module }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        Data inicial
                        <input type="date" name="data_inicio" value="{{ $filters['date_from'] }}">
                    </label>

                    <label>
                        Data final
                        <input type="date" name="data_fim" value="{{ $filters['date_to'] }}">
                    </label>
                </div>
            </form>
        </div>
    </section>
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

    <section class="section">
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
@endsection
