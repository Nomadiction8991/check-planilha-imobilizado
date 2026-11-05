@extends('layouts.migration')

@section('title', 'Igrejas | ' . config('app.name'))

@section('content')
    <style>
        .church-page-header h1 { font-size: 24px; line-height: 1.25; font-weight: 700; }
        .church-page-header .hero-copy { font-size: 16px; }
        .church-filters .filters { backdrop-filter: none; -webkit-backdrop-filter: none; box-shadow: none; }
        .church-filters label { font-size: 16px; }
        .church-filters :is(input, select, .btn), .church-list .btn { min-height: 44px; font-size: 16px; }
        .church-filters :is(input, select, button, a):focus-visible, .church-list :is(button, a):focus-visible { outline: 3px solid var(--ui-focus); outline-offset: 2px; }
        .church-list .btn.danger { color: var(--ui-danger); border-color: var(--ui-danger); }
        .church-list .empty-state p { margin: 8px 0 0; }
        @media (max-width: 639px) {
            .church-filters .filters-primary:has(.filters-query):has(.filters-principal) { grid-template-columns: minmax(0, 1fr); }
            .church-filters .filters-primary:has(.filters-query) .filters-actions { justify-self: stretch; }
            .church-list td::before { font-size: 14px; }
        }
        @media (min-width: 640px) { .church-page-header h1 { font-size: 32px; } }
    </style>
    @php
        $isLegacyAdmin = !empty($legacySessionUser['is_admin'] ?? false) || (bool) session('is_admin', false);
    @endphp

    <section class="hero church-page-header">
        <span class="eyebrow">Consulta e edição</span>
        <h1>Igrejas cadastradas no sistema.</h1>
        <p class="hero-copy">Consulte as igrejas e seus produtos ativos.</p>
    </section>

    @if (session('status') || $errors->any())
        <div class="flash-stack">
            @if (session('status'))
                <div class="flash {{ session('status_type', 'success') === 'error' ? 'error' : 'success' }}">
                    <strong>{{ session('status') }}</strong>
                </div>
            @endif
        </div>
    @endif

    <section class="section church-filters">
        <div data-sticky-filters>
            <form method="GET" action="{{ route('migration.churches.index') }}" class="filters" data-filter-autosubmit data-filter-message="Atualizando igrejas…">
                <div class="filters-bar">
                    <div class="filters-search">
                        <label for="churches-search">Buscar por código ou descrição</label>
                        <input id="churches-search" type="search" name="busca" value="{{ $filters->search }}" placeholder="BR 12-3456 ou descrição" data-filter-search data-filter-server>
                    </div>
                    <button type="submit" class="btn primary"><span class="material-symbols-outlined" aria-hidden="true">search</span><span class="mobile-action-text">Buscar</span></button>
                    <button type="button" class="btn" data-filter-open aria-haspopup="dialog" aria-controls="filtros-churches"><span class="material-symbols-outlined" aria-hidden="true">tune</span><span class="mobile-action-text">Filtros</span></button>
                </div>
                <span class="helper" role="status" aria-live="polite" data-filter-status></span>
                <dialog id="filtros-churches" class="filters-dialog" aria-labelledby="filtros-churches-titulo" data-filter-dialog>
                    <header class="filters-dialog-head">
                        <h2 id="filtros-churches-titulo">Filtros</h2>
                        <button type="button" class="btn ghost" data-filter-close aria-label="Fechar filtros"><span class="material-symbols-outlined" aria-hidden="true">close</span></button>
                    </header>
                    <div class="filters-dialog-fields">
                    <label for="churches-admin-search">
                        Buscar administração
                        <input
                            id="churches-admin-search"
                            type="search"
                            placeholder="Digite para filtrar"
                            autocomplete="off"
                            aria-controls="churches-admin-select"
                            data-churches-admin-search
                        >
                    </label>
                    <label class="filters-principal">
                        Administração
                        <select id="churches-admin-select" name="administracao_id" data-churches-admin-select data-filter-server>
                            <option value="">Todas</option>
                            @foreach ($administrations as $administration)
                                <option value="{{ $administration->id }}" @selected($filters->administrationId === $administration->id)>
                                    #{{ $administration->id }} - {{ $administration->descricao }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <p id="churches-admin-search-status" class="helper" role="status" aria-live="polite" hidden data-churches-admin-status></p>

                    <label class="filters-principal">
                        Estado (UF)
                        <select name="estado" id="churches-estado-select" data-filter-server>
                            <option value="">Todos os estados</option>
                            @foreach ($states as $stateCode => $stateLabel)
                                <option value="{{ $stateCode }}" @selected($filters->state === $stateCode)>
                                    {{ $stateCode }} - {{ $stateLabel }}
                                </option>
                            @endforeach
                        </select>
                    </label>


                    </div>
                    <footer class="filters-dialog-actions">
                        <a class="btn" href="{{ route('migration.churches.index') }}"><span class="material-symbols-outlined" aria-hidden="true">filter_alt_off</span>Limpar</a>
                        <button type="submit" class="btn primary"><span class="material-symbols-outlined" aria-hidden="true">check</span>Aplicar filtros</button>
                    </footer>
                </dialog>
            </form>
            @include('partials.filter-autosubmit')
            <script>
                (() => {
                    const search = document.querySelector('[data-churches-admin-search]');
                    const select = document.querySelector('[data-churches-admin-select]');
                    const status = document.querySelector('[data-churches-admin-status]');
                    if (!search || !select || !status) return;
                    const options = Array.from(select.options);
                    const placeholder = options.find((o) => o.value === '');
                    const adminOptions = options.filter((o) => o.value !== '');
                    const applyFilter = () => {
                        const term = search.value.trim().toLowerCase();
                        let visible = 0;
                        adminOptions.forEach((opt) => {
                            const match = term === '' || opt.textContent.toLowerCase().includes(term);
                            opt.hidden = !match;
                            opt.disabled = !match;
                            if (match) visible += 1;
                        });
                        if (select.value !== '' && select.options[select.selectedIndex]?.hidden) {
                            select.value = '';
                        }
                        if (visible === 0 && term !== '') {
                            status.textContent = 'Nenhuma administração encontrada para "' + search.value.trim() + '".';
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

    <section class="section church-list">
        <div class="section-head">
            <div>
                <h2>Lista de igrejas</h2>
                <p>Contagem de produtos considera apenas registros ativos.</p>
            </div>
        </div>

        <div class="table-shell">
            @if ($churches->isEmpty())
                <div class="empty-state">
                    <strong>Nenhuma igreja encontrada para o filtro informado.</strong>
                    <p>Ajuste os filtros ou limpe a busca para ver outras igrejas.</p>
                </div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descrição</th>
                            <th>Administração</th>
                            <th>Cidade</th>
                            <th>Setor</th>
                            <th>Produtos ativos</th>
                            @unless (session('public_acesso'))
                                <th>Ações</th>
                            @endunless
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($churches as $church)
                            <tr>
                                <td data-label="Código" data-card-title class="mono">{{ $church->codigo ?: 'Nenhum' }}</td>
                                <td data-label="Descrição" data-card-title>{{ $church->descricao ?: 'Sem descrição' }}</td>
                                <td data-label="Administração">
                                    @php
                                        $administracao = $church->administracao ?? null;
                                    @endphp

                                    {{ $administracao?->descricao ? '#' . $administracao->id . ' - ' . $administracao->descricao : 'Não vinculada' }}
                                </td>
                                <td data-label="Cidade">
                                    {{ trim(implode(' - ', array_filter([$church->cidade, $church->estado]))) ?: 'Nenhuma' }}
                                </td>
                                <td data-label="Setor">{{ $church->setor ?: 'Nenhum' }}</td>
                                <td data-label="Produtos ativos">{{ $church->active_products_count ?? 0 }}</td>
                                @unless (session('public_acesso'))
                                    <td data-label="Ações" data-card-actions>
                                        <div class="inline-actions">
                                            @if ($isLegacyAdmin || !empty($legacyPermissions['products.view'] ?? null))
                                                <a aria-label="Produtos" title="Produtos" class="btn" href="{{ route('migration.products.index', ['comum_id' => $church->id]) }}"><span class="material-symbols-outlined mobile-action-icon" aria-hidden="true">visibility</span><span class="mobile-action-text">Produtos</span></a>
                                            @endif
                                            @if ($isLegacyAdmin || !empty($legacyPermissions['churches.edit'] ?? null))
                                                <a aria-label="Editar" title="Editar" class="btn" href="{{ route('migration.churches.edit', ['church' => $church->id]) }}"><span class="material-symbols-outlined mobile-action-icon" aria-hidden="true">edit</span><span class="mobile-action-text">Editar</span></a>
                                            @endif
                                            @if ($isLegacyAdmin || !empty($legacyPermissions['churches.delete'] ?? null))
                                                <form method="POST" action="{{ route('migration.churches.delete-products') }}" data-confirm="Excluir todos os produtos desta igreja? Esta ação não pode ser desfeita.">
                                                    @csrf
                                                    <input type="hidden" name="comum_id" value="{{ $church->id }}">
                                                    <button class="btn danger" type="submit">Excluir produtos</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                @endunless
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @include('partials.pagination', ['paginator' => $churches])
            @endif
        </div>
    </section>
@endsection
