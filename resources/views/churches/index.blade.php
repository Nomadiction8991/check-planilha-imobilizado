@extends('layouts.migration')

@section('title', 'Igrejas | ' . config('app.name'))

@section('content')
    @php
        $isLegacyAdmin = !empty($legacySessionUser['is_admin'] ?? false) || (bool) session('is_admin', false);
    @endphp

    <section class="hero">
        <span class="eyebrow">Consulta e edição</span>
        <h1>Igrejas cadastradas no sistema.</h1>
        <p class="hero-copy">
            Esta tela permite filtrar, paginar, contar produtos ativos por igreja e acessar a área de produtos ou a
            edição do cadastro com rapidez.
        </p>
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

    <section class="section">
        <div class="filters" data-sticky-filters>
            <form method="GET" action="{{ route('migration.churches.index') }}">
                <div class="filters-primary">
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
                        <select id="churches-admin-select" name="administracao_id" data-churches-admin-select>
                            <option value="">Todas</option>
                            @foreach ($administrations as $administration)
                                <option value="{{ $administration->id }}" @selected($filters->administrationId === $administration->id)>
                                    #{{ $administration->id }} - {{ $administration->descricao }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <p id="churches-admin-search-status" class="helper" role="status" aria-live="polite" hidden data-churches-admin-status></p>

                    <label class="filters-query">
                        Buscar por código ou descrição
                        <input type="text" name="busca" value="{{ $filters->search }}" placeholder="BR 12-3456 ou descrição">
                    </label>

                    <div class="actions filters-actions">
                        <button class="btn primary" type="submit">Filtrar</button>
                        <a class="btn" href="{{ route('migration.churches.index') }}">Limpar</a>
                    </div>
                </div>
            </form>
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

    <section class="section">
        <div class="section-head">
            <div>
                <h2>Lista de igrejas</h2>
                <p>Contagem de produtos considera apenas registros ativos.</p>
            </div>
        </div>

        <div class="table-shell">
            @if ($churches->isEmpty())
                <div class="empty-state">Nenhuma igreja encontrada para o filtro informado.</div>
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
                                <td data-label="Código" class="mono">{{ $church->codigo ?: 'Nenhum' }}</td>
                                <td data-label="Descrição">{{ $church->descricao ?: 'Sem descrição' }}</td>
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
                                    <td data-label="Ações">
                                        <div class="inline-actions">
                                            @if ($isLegacyAdmin || !empty($legacyPermissions['products.view'] ?? null))
                                                <a class="btn" href="{{ route('migration.products.index', ['comum_id' => $church->id]) }}">Produtos</a>
                                            @endif
                                            @if ($isLegacyAdmin || !empty($legacyPermissions['churches.edit'] ?? null))
                                                <a class="btn" href="{{ route('migration.churches.edit', ['church' => $church->id]) }}">Editar</a>
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
