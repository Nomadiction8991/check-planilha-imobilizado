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
            @php
                $activeCount = $filters->activeCriteriaCount();
            @endphp
            <button type="button" class="product-filters-toggle" data-product-filters-toggle data-active-count="{{ $activeCount }}" aria-expanded="false" aria-controls="product-filters-panel-index">
                <span data-product-filters-toggle-label>{{ $activeCount > 0 ? 'Filtros · ' . $activeCount . ' ' . ($activeCount === 1 ? 'ativo' : 'ativos') : 'Filtros' }}</span>
                <span class="material-symbols-outlined product-filters-toggle__icon" aria-hidden="true">expand_more</span>
            </button>
            <div id="product-filters-panel-index" data-product-filters-panel>
            <form method="GET" action="{{ route('migration.products.index') }}" data-product-filter-form>
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
                        <span class="helper" role="status" aria-live="polite" data-product-filter-status></span>
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

    <script>
        (() => {
            document.querySelectorAll('[data-product-filter-form]').forEach((form) => {
                if (form.dataset.productFilterAutosubmit === 'ready') {
                    return;
                }

                form.dataset.productFilterAutosubmit = 'ready';
                const submitButton = form.querySelector('button[type="submit"]');
                const searchInput = form.querySelector('input[name="busca"]');
                let submitTimer = 0;
                let lastSignature = new URLSearchParams(new FormData(form)).toString();

                const getSignature = () => new URLSearchParams(new FormData(form)).toString();
                const status = form.querySelector('[data-product-filter-status]');
                const submitIfChanged = () => {
                    submitTimer = 0;
                    const signature = getSignature();
                    if (signature === lastSignature) {
                        return;
                    }

                    lastSignature = signature;
                    form.dataset.productFilterSubmitting = 'true';
                    if (status) {
                        status.textContent = 'Atualizando resultados…';
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
                    submitTimer = window.setTimeout(submitIfChanged, delay);
                };

                form.addEventListener('submit', () => {
                    window.clearTimeout(submitTimer);
                    form.dataset.productFilterSubmitting = 'true';
                });

                form.querySelectorAll('select[name="administracao_id"], select[name="comum_id"], select[name="estado"], select[name="dependencia_id"], select[name="tipo_bem_id"], select[name="status"]').forEach((select) => {
                    select.addEventListener('change', () => scheduleSubmit(80));
                });

                if (searchInput) {
                    searchInput.addEventListener('input', () => scheduleSubmit(350));
                    searchInput.addEventListener('search', () => scheduleSubmit(0));
                    searchInput.addEventListener('change', () => scheduleSubmit(0));
                }
            });
        })();
    </script>
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
@endsection
