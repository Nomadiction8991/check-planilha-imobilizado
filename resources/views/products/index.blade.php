@extends('layouts.migration')

@section('title', 'Produtos | ' . config('app.name'))

@section('content')
    @php
        $isLegacyAdmin = !empty($legacySessionUser['is_admin'] ?? false) || (bool) session('is_admin', false);
    @endphp

    <section class="hero">
        <span class="eyebrow">Consulta e manutenção</span>
        <h1>{{ $filters->onlyNew ? 'Produtos novos filtrados.' : 'Produtos ativos com filtro e manutenção.' }}</h1>
        <p class="hero-copy">
            Esta tela consulta o inventário com busca geral por código, descrição, dependência, tipo e status.
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
            <form method="GET" action="{{ route('migration.products.index') }}">
                <div class="filters-primary">
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

                    <label class="filters-query">
                        Busca geral
                        <input
                            type="text"
                            name="busca"
                            value="{{ $filters->search }}"
                            placeholder="Código, descrição, dependência, tipo ou status"
                        >
                    </label>

                    <div class="actions filters-actions">
                        <button class="btn primary" type="submit">Filtrar</button>
                        <a class="btn" href="{{ route('migration.products.index') }}">Limpar</a>
                    </div>
                </div>

                <div class="filters-advanced">
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
            <div class="inline-actions">
                @if ($isLegacyAdmin || !empty($legacyPermissions['products.edit'] ?? null))
                    <a class="btn" href="{{ route('migration.products.verification', array_filter(array_merge(['comum_id' => $filters->comumId], $filters->toQuery()))) }}">Verificação</a>
                @endif
                @if ($isLegacyAdmin || !empty($legacyPermissions['products.create'] ?? null))
                    <a class="btn primary" href="{{ route('migration.products.create', array_filter(['comum_id' => $filters->comumId])) }}">Novo produto</a>
                @endif
            </div>
        </div>

        <div class="table-shell">
            @if ($products->isEmpty())
                <div class="empty-state">Nenhum produto ativo encontrado para os filtros atuais.</div>
            @else
                <table>
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
                                $type = trim(implode(' - ', array_filter([
                                    data_get($product, 'tipoBem.codigo'),
                                    data_get($product, 'tipoBem.descricao'),
                                ])));
                                $description = \App\Support\LegacyProductNameSupport::formatCurrentName($product);
                            @endphp
                            <tr>
                                <td data-label="Produto">
                                    <div class="mono">{{ $product->codigo ?: 'sem código' }}</div>
                                    <div>{{ $description !== '' ? $description : 'Sem descrição' }}</div>
                                    @if ($type !== '')
                                        <div class="table-note">{{ $type }}</div>
                                    @endif
                                </td>
                                <td data-label="Dependência">{{ data_get($product, 'dependencia.descricao', 'Nenhuma') }}</td>
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
                                        <a class="btn" href="{{ route('migration.products.edit', [
                                            'product' => $product->id_produto,
                                            'return_url' => url()->full(),
                                        ]) }}">
                                            Editar
                                        </a>
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
@endsection
