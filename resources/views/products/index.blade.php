@extends('layouts.migration')

@section('title', 'Produtos | ' . config('app.name'))

@section('content')
    @php
        $isLegacyAdmin = !empty($legacySessionUser['is_admin'] ?? false) || (bool) session('is_admin', false);
        $canEditProducts = $isLegacyAdmin || !empty($legacyPermissions['products.edit'] ?? false);
    @endphp

    <section class="hero">
        <span class="eyebrow">Consulta e manutenção</span>
        <h1>{{ $filters->onlyNew ? 'Produtos novos filtrados.' : 'Produtos ativos com filtro e manutenção.' }}</h1>
        <p class="hero-copy">
            Esta tela consulta o inventário com busca geral por código, descrição, dependência, tipo e status.
        </p>
        <div class="inline-actions product-header-actions">
            @if ($isLegacyAdmin || !empty($legacyPermissions['products.edit'] ?? null))
                <a class="btn" href="{{ route('migration.products.verification', array_filter(array_merge(['comum_id' => $filters->comumId], $filters->toQuery()))) }}">Verificação</a>
            @endif
            @if ($isLegacyAdmin || !empty($legacyPermissions['products.create'] ?? null))
                <a class="btn primary" href="{{ route('migration.products.create', array_filter(['comum_id' => $filters->comumId])) }}">Novo produto</a>
            @endif
        </div>
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
        <div data-sticky-filters>
            <form method="GET" action="{{ route('migration.products.index') }}" class="filters" data-filter-autosubmit data-filter-message="Atualizando resultados…">
                <div class="filters-bar">
                    <div class="filters-search">
                        <label for="busca-produtos">Busca geral</label>
                        <input id="busca-produtos" type="search" name="busca" value="{{ $filters->search }}" placeholder="Código, descrição, dependência, tipo ou status" data-filter-search data-filter-server>
                    </div>
                    <button type="submit" class="btn primary"><span class="material-symbols-outlined" aria-hidden="true">search</span>Buscar</button>
                    <button type="button" class="btn" data-filter-open aria-haspopup="dialog" aria-controls="filtros-produtos"><span class="material-symbols-outlined" aria-hidden="true">tune</span>Filtros</button>
                </div>
                <dialog id="filtros-produtos" class="filters-dialog" aria-labelledby="filtros-produtos-titulo" data-filter-dialog>
                    <header class="filters-dialog-head">
                        <h2 id="filtros-produtos-titulo">Filtros</h2>
                        <button type="button" class="btn ghost" data-filter-close aria-label="Fechar filtros"><span class="material-symbols-outlined" aria-hidden="true">close</span></button>
                    </header>
                    <div class="filters-dialog-fields">
                    <label for="product-admin-search">
                        Buscar administração
                        <input
                            id="product-admin-search"
                            type="search"
                            placeholder="Digite para filtrar"
                            autocomplete="off"
                            aria-controls="product-admin-select"
                            data-product-admin-search
                        >
                    </label>
                    <label class="filters-principal">
                        Administração
                        <select id="product-admin-select" name="administracao_id" data-product-admin-select>
                            <option value="">Todas</option>
                            @foreach ($administrations as $administration)
                                <option value="{{ $administration->id }}" @selected($filters->administrationId === $administration->id)>
                                    #{{ $administration->id }} - {{ $administration->descricao }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <p id="product-admin-search-status" class="helper" role="status" aria-live="polite" hidden data-product-admin-status></p>

                    <label for="product-church-search">
                        Buscar igreja
                        <input
                            id="product-church-search"
                            type="search"
                            placeholder="Digite para filtrar"
                            autocomplete="off"
                            aria-controls="product-church-select"
                            data-product-church-search
                        >
                    </label>
                    <label class="filters-principal">
                        Igreja
                        <select id="product-church-select" name="comum_id" data-product-church-select>
                            <option value="">Todas</option>
                            @foreach ($churches as $church)
                                <option value="{{ $church->id }}" @selected($filters->comumId === $church->id)>
                                    {{ $church->codigo }} - {{ $church->descricao }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <p id="product-church-search-status" class="helper" role="status" aria-live="polite" hidden data-product-church-status></p>

                    <label class="filters-principal">
                        Estado (UF)
                        <select name="estado" id="products-estado-select">
                            <option value="">Todos os estados</option>
                            @foreach ($states as $stateCode => $stateLabel)
                                <option value="{{ $stateCode }}" @selected($filters->state === $stateCode)>
                                    {{ $stateCode }} - {{ $stateLabel }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        Dependência
                        <select name="dependencia_id">
                            <option value="">Todas</option>
                            @foreach ($dependencies as $dependency)
                                <option value="{{ $dependency->id }}" @selected($filters->dependencyId === $dependency->id)>
                                    {{ $dependency->descricao }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        Tipo de bem
                        <select name="tipo_bem_id">
                            <option value="">Todos</option>
                            @foreach ($assetTypes as $assetType)
                                <option value="{{ $assetType->id }}" @selected($filters->assetTypeId === $assetType->id)>
                                    {{ $assetType->codigo }} - {{ $assetType->descricao }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        Status
                        <select name="status">
                            <option value="">Todos</option>
                            @foreach ($statusOptions as $statusKey => $statusLabel)
                                <option value="{{ $statusKey }}" @selected($filters->status === $statusKey)>
                                    {{ $statusLabel }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    </div>
                    <footer class="filters-dialog-actions">
                        <a class="btn" href="{{ route('migration.products.index') }}"><span class="material-symbols-outlined" aria-hidden="true">filter_alt_off</span>Limpar</a>
                        <button type="submit" class="btn primary"><span class="material-symbols-outlined" aria-hidden="true">check</span>Aplicar filtros</button>
                    </footer>
                </dialog>
            </form>
            @include('products.partials.active-filter-chips')
        </div>
    </section>

    <section class="section">
        <div class="section-head">
            <div>
                <h2>Lista de produtos</h2>
                <p>
                    {{ $filters->onlyNew
                        ? 'A listagem está restrita aos itens marcados como novos.'
                        : 'Somente itens ativos aparecem aqui. Criação e edição principal estão disponíveis.' }}
                </p>
            </div>
        </div>

        <div class="table-shell product-listing">
            @if ($products->isEmpty())
                <div class="empty-state">Nenhum produto ativo encontrado para os filtros atuais.</div>
            @else
                <table aria-label="Lista de produtos">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Dependência</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            @php
                                $type = \App\Support\LegacyProductClassificationSupport::currentTypeLabel($product);
                                $description = \App\Support\LegacyProductNameSupport::formatCurrentName($product);
                                $dependency = \App\Support\LegacyProductClassificationSupport::currentDependencyDescription($product);
                            @endphp
                            <tr>
                                <td data-label="Produto">
                                    <div class="mono">{{ $product->codigo ?: 'sem código' }}</div>
                                    <div>{{ $description !== '' ? $description : 'Sem descrição' }}</div>
                                    @if ($type !== '')
                                        <div class="table-note">{{ $type }}</div>
                                    @endif
                                </td>
                                <td data-label="Dependência">{{ $dependency !== '' ? $dependency : 'Nenhuma' }}</td>
                                <td data-label="Status">
                                    @if ((int) $product->imprimir_14_1 === 1)
                                        <span class="capsule dark">14.1</span>
                                    @endif
                                    @if ($product->nota_numero !== null && $product->nota_numero !== '')
                                        <span class="capsule warn">Nota fiscal</span>
                                    @endif
                                    @if ((int) $product->novo === 1)
                                        <span class="capsule accent">Novo</span>
                                    @endif
                                    @if ((int) $product->editado === 1)
                                        <span class="capsule">Editado</span>
                                    @endif
                                </td>
                                <td data-label="Ações">
                                    <div class="inline-actions">
                                        @if ($canEditProducts)
                                            <a class="btn" href="{{ route('migration.products.edit', [
                                                'product' => $product->id_produto,
                                                'return_url' => url()->full(),
                                            ]) }}">
                                                Editar
                                            </a>
                                        @else
                                            <span class="table-note" role="status">Somente consulta</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @include('partials.pagination', ['paginator' => $products])
            @endif
        </div>
    </section>

    <style>
        .product-header-actions { margin-top: 20px; }
        .product-header-actions .btn, .product-listing .btn, .filters .btn { min-height: 44px; }
        .product-listing { background: var(--surface); border: 1px solid var(--line); box-shadow: var(--shadow-card); }
        .product-listing table { background: var(--surface); }
        .product-listing th { background: var(--surface-soft); }
        .product-listing .capsule { font-size: 14px; background: var(--surface-soft); color: var(--ink); }
        .product-listing .capsule.accent { background: var(--accent-soft); color: var(--accent); }
        @media (max-width: 639px) {
            .product-listing tbody tr { border-radius: var(--radius-md); box-shadow: var(--shadow-card); }
        }
        @media (min-width: 640px) and (max-width: 860px) {
            .product-listing table { display: table; }
            .product-listing thead { display: table-header-group; }
            .product-listing tbody { display: table-row-group; padding: 0; }
            .product-listing tr { display: table-row; padding: 0; border: 0; background: none; box-shadow: none; }
            .product-listing th, .product-listing td { display: table-cell; padding: 15px 18px; border-top: 1px solid var(--line); }
            .product-listing td::before { display: none; }
        }
    </style>

    <script>
        (() => {
            const adminSearch = document.querySelector('[data-product-admin-search]');
            const adminSelect = document.querySelector('[data-product-admin-select]');
            const adminStatus = document.querySelector('[data-product-admin-status]');
            if (adminSearch && adminSelect && adminStatus) {
                const adminOptionsList = Array.from(adminSelect.options);
                const adminPlaceholder = adminOptionsList.find((o) => o.value === '');
                const realAdminOptions = adminOptionsList.filter((o) => o.value !== '');
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
                        adminSelect.dispatchEvent(new Event('change', { bubbles: true }));
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

            const search = document.querySelector('[data-product-church-search]');
            const select = document.querySelector('[data-product-church-select]');
            const status = document.querySelector('[data-product-church-status]');
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
                    select.dispatchEvent(new Event('change', { bubbles: true }));
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
    @include('partials.filter-autosubmit')
@endsection
