@extends('layouts.migration')

@section('title', 'Tipos de Bem | ' . config('app.name'))

@section('content')
    @php
        $isLegacyAdmin = !empty($legacySessionUser['is_admin'] ?? false) || (bool) session('is_admin', false);
    @endphp

    <section class="hero">
        <span class="eyebrow">Cadastro de bens</span>
        <h1>Tipos de bem disponíveis no sistema.</h1>
        <p class="hero-copy">
            O catálogo de tipos de bem está navegável e editável neste sistema. A página permite localizar códigos,
            medir uso real por produtos ativos e operar o cadastro com segurança.
        </p>
    </section>

    @if (session('status') || $errors->any())
        <div class="flash-stack">
            @if (session('status'))
                <div class="flash {{ session('status_type', 'success') === 'error' ? 'error' : 'success' }}">
                    <strong>{{ session('status') }}</strong>
                    @if (session('status_detail'))
                        <div class="field-note">{{ session('status_detail') }}</div>
                    @endif
                </div>
            @endif

            @if ($errors->any())
                <div class="flash error">
                    <strong>Não foi possível salvar o tipo de bem.</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    <section class="section">
        <div class="filters" data-sticky-filters>
            <form method="GET" action="{{ route('migration.asset-types.index') }}">
                <div class="filters-primary">
                    <label for="asset-types-admin-search">
                        Buscar administração
                        <input
                            id="asset-types-admin-search"
                            type="search"
                            placeholder="Digite para filtrar"
                            autocomplete="off"
                            aria-controls="asset-types-admin-select"
                            data-asset-types-admin-search
                        >
                    </label>
                    <label class="filters-principal">
                        Administração
                        <select id="asset-types-admin-select" name="administracao_id" data-asset-types-admin-select>
                            <option value="">Todas</option>
                            @foreach ($administrations as $administration)
                                <option value="{{ $administration->id }}" @selected($filters->administrationId === $administration->id)>
                                    #{{ $administration->id }} - {{ $administration->descricao }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <p id="asset-types-admin-search-status" class="helper" role="status" aria-live="polite" hidden data-asset-types-admin-status></p>

                    <label class="filters-principal">
                        Estado (UF)
                        <select name="estado" id="asset-types-estado-select">
                            <option value="">Todos os estados</option>
                            @foreach ($states as $stateCode => $stateLabel)
                                <option value="{{ $stateCode }}" @selected($filters->state === $stateCode)>
                                    {{ $stateCode }} - {{ $stateLabel }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="filters-query">
                        Buscar por código ou descrição
                        <input type="text" name="busca" value="{{ $filters->search }}" placeholder="4 ou CADEIRA">
                    </label>

                    <div class="actions filters-actions">
                        <button class="btn primary" type="submit">Filtrar</button>
                        <a class="btn" href="{{ route('migration.asset-types.index') }}">Limpar</a>
                    </div>
                </div>
            </form>
            <script>
                (() => {
                    const search = document.querySelector('[data-asset-types-admin-search]');
                    const select = document.querySelector('[data-asset-types-admin-select]');
                    const status = document.querySelector('[data-asset-types-admin-status]');
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
                })();
            </script>
        </div>
    </section>

    <section class="section">
        <div class="section-head">
            <div>
                <h2>Lista de tipos de bem</h2>
                <p>A contagem considera apenas produtos ativos vinculados a cada tipo.</p>
            </div>
            <div class="inline-actions">
                @if ($isLegacyAdmin || !empty($legacyPermissions['asset-types.create'] ?? null))
                    <a class="btn primary" href="{{ route('migration.asset-types.create') }}">Novo tipo de bem</a>
                @endif
            </div>
        </div>

        <div class="table-shell">
            @if ($assetTypes->isEmpty())
                <div class="empty-state">Nenhum tipo de bem encontrado para os filtros atuais.</div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Administração</th>
                            <th>Código</th>
                            <th>Descrição</th>
                            <th>Produtos ativos</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($assetTypes as $assetType)
                            <tr>
                                <td data-label="Administração">
                                    {{ data_get($assetType, 'administracao.id') ? '#' . data_get($assetType, 'administracao.id') : 'Global' }}
                                    @if (data_get($assetType, 'administracao.descricao'))
                                        <div class="table-note">{{ data_get($assetType, 'administracao.descricao') }}</div>
                                    @endif
                                </td>
                                <td data-label="Código" class="mono">{{ $assetType->codigo }}</td>
                                <td data-label="Descrição">{{ $assetType->descricao ?: 'Sem descrição' }}</td>
                                <td data-label="Produtos ativos">{{ $assetType->active_products_count }}</td>
                                <td data-label="Ações">
                                    <div class="inline-actions">
                                        @if ($isLegacyAdmin || !empty($legacyPermissions['products.view'] ?? null))
                                            <a class="btn" href="{{ route('migration.products.index', ['tipo_bem_id' => $assetType->id]) }}">Ver produtos</a>
                                        @endif
                                        @if ($isLegacyAdmin || !empty($legacyPermissions['asset-types.edit'] ?? null))
                                            <a class="btn" href="{{ route('migration.asset-types.edit', ['assetType' => $assetType->id]) }}">Editar</a>
                                        @endif
                                        @if ($isLegacyAdmin || !empty($legacyPermissions['asset-types.delete'] ?? null))
                                            <form method="POST" action="{{ route('migration.asset-types.destroy', ['assetType' => $assetType->id]) }}" data-confirm="Excluir este tipo de bem?">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn" type="submit">
                                                    Excluir
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @include('partials.pagination', ['paginator' => $assetTypes])
            @endif
        </div>
    </section>
@endsection
