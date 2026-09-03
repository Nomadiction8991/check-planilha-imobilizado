@extends('layouts.migration')

@section('title', 'Verificação de Produtos | ' . config('app.name'))

@section('content')
    @php
        $isLegacyAdmin = !empty($legacySessionUser['is_admin'] ?? false) || (bool) session('is_admin', false);
        $canEditProducts = $isLegacyAdmin || !empty($legacyPermissions['products.edit'] ?? false);
        $selectedChurchId = $filters->comumId;
        $copyLabelsQuery = array_filter([
            'comum_id' => $selectedChurchId,
            'dependencia' => $filters->dependencyId,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    @endphp

    <style>
        .identification-block {
            display: grid;
            gap: 3px;
        }

        .identification-primary {
            font-size: 16px;
            font-weight: 400;
            font-family: inherit;
            letter-spacing: 0;
            color: var(--ink);
            line-height: 1.22;
        }

        .identification-secondary {
            font-size: 16px;
            font-weight: 400;
            font-family: inherit;
            color: var(--ink);
            line-height: 1.22;
        }

        .identification-tertiary {
            font-size: 16px;
            font-weight: 400;
            font-family: inherit;
            color: var(--muted);
            line-height: 1.22;
        }

        .verification-hero {
            position: relative;
            overflow: hidden;
            padding: 24px 26px;
            border: 1px solid var(--line);
            border-radius: 26px;
            background:
                radial-gradient(circle at top right, color-mix(in srgb, var(--accent-soft) 92%, transparent), transparent 42%),
                linear-gradient(180deg, color-mix(in srgb, var(--surface-strong) 84%, transparent), var(--surface));
            box-shadow: var(--shadow-soft);
        }

        .verification-hero::after {
            content: '';
            position: absolute;
            inset: auto -44px -52px auto;
            width: 180px;
            height: 180px;
            border-radius: 999px;
            background: radial-gradient(circle, color-mix(in srgb, var(--accent) 16%, transparent) 0%, transparent 70%);
            pointer-events: none;
        }

        .verification-hero .hero-copy {
            max-width: 64ch;
        }

        .verification-edit-btn {
            min-width: 46px;
            padding-inline: 0;
            justify-content: center;
        }

        .verification-edit-btn .material-symbols-outlined {
            font-size: 20px;
            line-height: 1;
        }

        .verification-mobile-icon {
            display: none;
            font-size: 18px;
            line-height: 1;
            color: var(--muted);
            flex-shrink: 0;
        }

        .verification-mobile-icon--verified {
            color: var(--accent);
        }

        @media (max-width: 860px) {
            .verification-hero {
                padding: 20px 20px 18px;
            }

            .verification-table-shell table {
                border-collapse: separate;
                border-spacing: 0;
            }

            .verification-table-shell thead {
                display: none;
            }

            .verification-table-shell tbody {
                display: grid;
                gap: 14px;
                padding: 14px;
            }

            .verification-table-shell tbody tr {
                display: grid;
                grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto;
                grid-template-areas:
                    'verified label actions'
                    'identification identification identification'
                    'observation observation observation';
                gap: 12px 10px;
                padding: 14px;
                border: 1px solid var(--line);
                border-radius: 16px;
                background: var(--surface);
                box-shadow: var(--shadow-soft);
            }

            .verification-table-shell td {
                display: block;
                min-width: 0;
                padding: 0;
                border: 0;
            }

            .verification-table-shell td::before {
                display: none;
                content: none;
            }

            .verification-cell--verified {
                grid-area: verified;
            }

            .verification-cell--label {
                grid-area: label;
            }

            .verification-cell--actions {
                grid-area: actions;
                justify-self: end;
                align-self: start;
            }

            .verification-cell--identification {
                grid-area: identification;
            }

            .verification-cell--observation {
                grid-area: observation;
            }

            .verification-cell--verified,
            .verification-cell--label,
            .verification-cell--actions {
                align-self: start;
            }

            .verification-cell--verified .check-inline,
            .verification-cell--label .check-inline {
                width: 100%;
                justify-content: center;
                padding: 10px 8px;
                border: 1px solid var(--line);
                border-radius: 14px;
                background: rgba(255, 255, 255, 0.58);
            }

            html[data-theme='dark'] .verification-cell--verified .check-inline,
            html[data-theme='dark'] .verification-cell--label .check-inline {
                background: linear-gradient(180deg, rgba(47, 43, 38, 0.98), rgba(33, 31, 27, 0.96));
                border-color: rgba(255, 255, 255, 0.14);
            }

            html[data-theme='dark'] .verification-cell--verified .check-inline {
                border-color: rgba(145, 221, 209, 0.22);
                color: #d7fff7;
            }

            html[data-theme='dark'] .verification-cell--label .check-inline {
                border-color: rgba(239, 190, 130, 0.22);
                color: #ffe4bf;
            }

            .verification-mobile-icon {
                display: inline-flex;
                align-items: center;
            }

            .verification-cell--actions .btn {
                min-height: 44px;
                padding-inline: 14px;
            }

            .verification-cell--actions .verification-edit-btn {
                min-width: 46px;
                padding-inline: 0;
            }

            .verification-cell--observation input[type='text'] {
                min-height: 46px;
            }

            .identification-block {
                gap: 2px;
            }

            .identification-primary,
            .identification-secondary,
            .identification-tertiary {
                font-size: 15px;
            }
        }

        @media (min-width: 861px) {
            .verification-hero {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                align-items: center;
                gap: 14px 24px;
            }

            .verification-hero .hero-actions {
                align-self: center;
                justify-self: end;
                margin-top: 0;
            }
        }
    </style>

    <section class="hero verification-hero">
        <span class="eyebrow">Checklist de impressão</span>
        <h1>Verificação de produtos para impressão.</h1>
        <p class="hero-copy">
            Marque os itens que seguem para etiqueta e registre observações curtas antes da impressão.
        </p>
        @if ($selectedChurchId && !empty($legacyPermissions['products.view'] ?? null))
            <div class="hero-actions">
                <a class="btn" href="{{ route('migration.labels.index', $copyLabelsQuery) }}">Abrir etiquetas</a>
            </div>
        @endif
    </section>

    @if (session('status') || $errors->any())
        <div class="flash-stack">
            @if (session('status'))
                <div class="flash {{ session('status_type', 'success') === 'error' ? 'error' : 'success' }}">
                    <strong>{{ session('status') }}</strong>
                </div>
            @endif
            @if ($errors->any())
                <div class="flash error">
                    <strong>Revise os campos destacados antes de salvar o checklist.</strong>
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
            @php
                $activeCount = $filters->activeCriteriaCount();
            @endphp
            <button type="button" class="product-filters-toggle" data-product-filters-toggle data-active-count="{{ $activeCount }}" aria-expanded="false" aria-controls="product-filters-panel-verification">
                <span data-product-filters-toggle-label>{{ $activeCount > 0 ? 'Filtros · ' . $activeCount . ' ' . ($activeCount === 1 ? 'ativo' : 'ativos') : 'Filtros' }}</span>
                <span class="material-symbols-outlined product-filters-toggle__icon" aria-hidden="true">expand_more</span>
            </button>
            <div id="product-filters-panel-verification" data-product-filters-panel>
            <form method="GET" action="{{ route('migration.products.verification') }}" data-product-filter-form>
                <div class="filters-primary">
                    <label for="product-admin-search-verification">
                        Buscar administração
                        <input
                            id="product-admin-search-verification"
                            type="search"
                            placeholder="Digite para filtrar"
                            autocomplete="off"
                            aria-controls="product-admin-select-verification"
                            data-product-admin-search
                        >
                    </label>
                    <label class="filters-principal">
                        Administração
                        <div class="filters-principal__controls">
                            <select id="product-admin-select-verification" name="administracao_id" data-product-admin-select>
                                <option value="">Todas</option>
                                @foreach ($administrations as $administration)
                                    <option value="{{ $administration->id }}" @selected($filters->administrationId === $administration->id)>
                                        #{{ $administration->id }} - {{ $administration->descricao }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </label>
                    <p id="product-admin-search-status-verification" class="helper" role="status" aria-live="polite" hidden data-product-admin-status></p>

                    <label for="product-church-search-verification">
                        Buscar igreja
                        <input
                            id="product-church-search-verification"
                            type="search"
                            placeholder="Digite para filtrar"
                            autocomplete="off"
                            aria-controls="product-church-select-verification"
                            data-product-church-search
                        >
                    </label>
                    <label class="filters-principal">
                        Igreja
                        <div class="filters-principal__controls">
                            <select id="product-church-select-verification" name="comum_id" data-product-church-select>
                                <option value="">Todas</option>
                                @foreach ($churches as $church)
                                    <option value="{{ $church->id }}" @selected($filters->comumId === $church->id)>
                                        {{ $church->codigo }} - {{ $church->descricao }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </label>
                    <p id="product-church-search-status-verification" class="helper" role="status" aria-live="polite" hidden data-product-church-status></p>

                    <label class="filters-principal">
                        Estado (UF)
                        <div class="filters-principal__controls">
                            <select name="estado" id="product-verification-estado-select">
                                <option value="">Todos os estados</option>
                                @foreach ($states as $stateCode => $stateLabel)
                                    <option value="{{ $stateCode }}" @selected($filters->state === $stateCode)>
                                        {{ $stateCode }} - {{ $stateLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
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
                        <a class="btn" href="{{ route('migration.products.verification') }}">Limpar</a>
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
                <h2>Checklist de produtos</h2>
                <p>
                    Selecione os produtos para impressão e deixe observações curtas no mesmo fluxo.
                </p>
            </div>
            <div class="inline-actions">
                <a class="btn" href="{{ route('migration.products.index', array_filter(array_merge(['comum_id' => $selectedChurchId], $filters->toQuery()))) }}">Voltar aos produtos</a>
                @if ($selectedChurchId)
                    <a class="btn primary" href="{{ route('migration.labels.index', $copyLabelsQuery) }}">Ver etiquetas</a>
                @endif
            </div>
        </div>

        @if ($products->isEmpty())
            <div class="empty-state">
                <strong>Nenhum produto encontrado para os filtros atuais.</strong>
                <p>Selecione outra igreja, ajuste os filtros ou volte para a listagem principal.</p>
            </div>
        @else
            <div
                class="table-shell verification-table-shell"
                data-verification-autosave
                data-autosave-url="{{ route('migration.products.verification.sync') }}"
            >
                @csrf

                    <table>
                        <thead>
                            <tr>
                                <th>Verificado</th>
                                <th>Etiqueta</th>
                                <th>Identificação</th>
                                <th>Observação</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $index => $product)
                                @php
                                    $code = trim((string) data_get($product, 'codigo', ''));
                                    $codeParts = $code !== '' ? explode('/', $code) : [];
                                    $displayCode = $codeParts !== [] ? trim((string) $codeParts[array_key_last($codeParts)]) : '';
                                    $typeDescription = \App\Support\LegacyProductClassificationSupport::currentTypeDescription($product);
                                    $productDescription = \App\Support\LegacyProductNameSupport::formatCurrentName($product);
                                    $dependencyDescription = \App\Support\LegacyProductClassificationSupport::currentDependencyDescription($product);
                                    $mainDescription = $productDescription !== '' ? $productDescription : 'Sem descrição';
                                    $currentObservation = trim((string) data_get($product, 'observacao', ''));
                                    $observation = old("itens.$index.observacao", $currentObservation);
                                    $printLabel = old("itens.$index.imprimir_etiqueta", (int) data_get($product, 'imprimir_etiqueta', 0) === 1);
                                    $verified = old("itens.$index.verificado", (int) data_get($product, 'checado', 0) === 1);
                                    $verified = (bool) $verified || (bool) $printLabel || trim((string) $observation) !== '';
                                @endphp
                                <tr data-product-id="{{ data_get($product, 'id_produto') }}" data-church-id="{{ data_get($product, 'comum_id') }}">
                                    <td class="verification-cell--verified" data-label="Verificado">
                                        <label class="check-inline">
                                            <input
                                                type="checkbox"
                                                name="verificado"
                                                value="1"
                                                @checked((bool) $verified)
                                                aria-label="Marcar {{ $code !== '' ? $code : 'produto' }} como verificado"
                                            >
                                            <span class="verification-mobile-icon verification-mobile-icon--verified material-symbols-outlined" aria-hidden="true">
                                                check_circle
                                            </span>
                                        </label>
                                    </td>
                                    <td class="verification-cell--label" data-label="Etiqueta">
                                        <label class="check-inline">
                                            <input
                                                type="checkbox"
                                                name="imprimir_etiqueta"
                                                value="1"
                                                @checked((bool) $printLabel)
                                                aria-label="Marcar {{ $code !== '' ? $code : 'produto' }} para impressão"
                                            >
                                            <span class="verification-mobile-icon material-symbols-outlined" aria-hidden="true">
                                                sell
                                            </span>
                                        </label>
                                    </td>
                                    <td class="verification-cell--identification" data-label="Identificação">
                                        <div class="identification-block">
                                            <div class="identification-primary mono">
                                                {{ $displayCode !== '' ? $displayCode : 'sem código' }}
                                            </div>
                                            <div class="identification-secondary">
                                                {{ $mainDescription }}
                                            </div>
                                            <div class="identification-tertiary table-note">
                                                {{ $typeDescription !== '' ? $typeDescription : 'Nenhum' }} >> {{ $dependencyDescription !== '' ? $dependencyDescription : 'Nenhuma' }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="verification-cell--observation" data-label="Observação">
                                        <input
                                            type="text"
                                            name="observacao"
                                            value="{{ $observation }}"
                                            maxlength="255"
                                            placeholder="Observação rápida"
                                            title="{{ $observation }}"
                                        >
                                    </td>
                                    <td class="verification-cell--actions" data-label="Ações">
                                        @if ($canEditProducts)
                                            <a
                                                class="btn verification-edit-btn"
                                                href="{{ route('migration.products.edit', [
                                                    'product' => data_get($product, 'id_produto'),
                                                    'return_url' => url()->full(),
                                                ]) }}"
                                                aria-label="Editar cadastro do produto {{ $code !== '' ? $code : 'selecionado' }}"
                                                title="Editar cadastro"
                                            >
                                                <span class="material-symbols-outlined" aria-hidden="true">edit</span>
                                            </a>
                                        @else
                                            <span class="table-note" role="status">Somente consulta</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @include('partials.pagination', ['paginator' => $products])
                    <div class="inline-actions">
                        @if ($selectedChurchId)
                            <a class="btn" href="{{ route('migration.labels.index', $copyLabelsQuery) }}">Ir para etiquetas</a>
                        @endif
                    </div>
            </div>
        @endif
    </section>

    @if ($products->isNotEmpty())
        <script>
            (() => {
                const root = document.querySelector('[data-verification-autosave]');
                if (!root) {
                    return;
                }

                const autosaveUrl = root.dataset.autosaveUrl || '';
                const csrfToken = root.querySelector('input[name="_token"]')?.value || '';
                const saveTimers = new WeakMap();
                const saveControllers = new WeakMap();

                const buildPayload = (row) => {
                    const productId = row.dataset.productId || '';
                    const churchId = row.dataset.churchId || '';
                    const observationInput = row.querySelector('input[name="observacao"]');
                    const verifiedInput = row.querySelector('input[name="verificado"]');
                    const labelInput = row.querySelector('input[name="imprimir_etiqueta"]');

                    const payload = new FormData();
                    payload.append('_token', csrfToken);
                    payload.append('comum_id', churchId);
                    payload.append('produto_id', productId);
                    payload.append('verificado', verifiedInput instanceof HTMLInputElement && verifiedInput.checked ? '1' : '0');
                    payload.append('imprimir_etiqueta', labelInput instanceof HTMLInputElement && labelInput.checked ? '1' : '0');
                    payload.append('observacao', observationInput instanceof HTMLInputElement ? observationInput.value : '');

                    return payload;
                };

                const applyServerState = (row, state) => {
                    const verifiedInput = row.querySelector('input[name="verificado"]');
                    const labelInput = row.querySelector('input[name="imprimir_etiqueta"]');

                    if (verifiedInput instanceof HTMLInputElement && Object.prototype.hasOwnProperty.call(state, 'checked')) {
                        verifiedInput.checked = Boolean(state.checked);
                    }

                    if (labelInput instanceof HTMLInputElement && Object.prototype.hasOwnProperty.call(state, 'print_label')) {
                        labelInput.checked = Boolean(state.print_label);
                    }
                };

                const syncDerivedState = (row) => {
                    const verifiedInput = row.querySelector('input[name="verificado"]');
                    const labelInput = row.querySelector('input[name="imprimir_etiqueta"]');
                    const observationInput = row.querySelector('input[name="observacao"]');

                    if (
                        !(verifiedInput instanceof HTMLInputElement)
                        || !(labelInput instanceof HTMLInputElement)
                        || !(observationInput instanceof HTMLInputElement)
                    ) {
                        return;
                    }

                    if ((labelInput.checked || observationInput.value.trim() !== '') && !verifiedInput.checked) {
                        verifiedInput.checked = true;
                    }
                };

                const saveRow = async (row) => {
                    if (autosaveUrl === '' || csrfToken === '' || row.dataset.productId === '' || row.dataset.churchId === '') {
                        return;
                    }

                    const previousController = saveControllers.get(row);
                    if (previousController) {
                        previousController.abort();
                    }

                    const controller = new AbortController();
                    saveControllers.set(row, controller);

                    try {
                        const response = await fetch(autosaveUrl, {
                        method: 'POST',
                        body: buildPayload(row),
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-No-Loader': 'true',
                            'Accept': 'application/json',
                        },
                        credentials: 'same-origin',
                        signal: controller.signal,
                        });

                        if (!response.ok) {
                            console.error('Falha ao salvar o checklist automaticamente.');
                            return;
                        }

                        try {
                            const data = await response.json();
                            applyServerState(row, data);
                        } catch (parseError) {
                            console.error(parseError);
                        }

                        row.dataset.syncState = 'saved';
                        window.setTimeout(() => {
                            if (row.dataset.syncState === 'saved') {
                                delete row.dataset.syncState;
                            }
                        }, 1200);
                    } catch (error) {
                        if (error instanceof DOMException && error.name === 'AbortError') {
                            return;
                        }

                        console.error(error);
                        row.dataset.syncState = 'error';
                    } finally {
                        if (saveControllers.get(row) === controller) {
                            saveControllers.delete(row);
                        }
                    }
                };

                const scheduleSave = (row, delay = 0) => {
                    const existingTimer = saveTimers.get(row);
                    if (existingTimer) {
                        window.clearTimeout(existingTimer);
                    }

                    if (delay <= 0) {
                        void saveRow(row);
                        return;
                    }

                    const timer = window.setTimeout(() => {
                        saveTimers.delete(row);
                        void saveRow(row);
                    }, delay);

                    saveTimers.set(row, timer);
                };

                root.querySelectorAll('tbody tr[data-product-id]').forEach((row) => {
                    const observationInput = row.querySelector('input[name="observacao"]');
                    const checkboxes = row.querySelectorAll('input[type="checkbox"]');

                    syncDerivedState(row);

                    checkboxes.forEach((checkbox) => {
                        checkbox.addEventListener('change', () => {
                            syncDerivedState(row);
                            scheduleSave(row);
                        });
                    });

                    if (observationInput instanceof HTMLInputElement) {
                        observationInput.addEventListener('input', () => {
                            syncDerivedState(row);
                            scheduleSave(row, 500);
                        });

                        observationInput.addEventListener('change', () => {
                            syncDerivedState(row);
                            scheduleSave(row);
                        });

                        observationInput.addEventListener('blur', () => {
                            syncDerivedState(row);
                            scheduleSave(row);
                        });
                    }
                });
            })();
        </script>
    @endif
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
