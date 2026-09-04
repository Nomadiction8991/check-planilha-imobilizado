@extends('layouts.migration')

@section('title', 'Dependências | ' . config('app.name'))

@section('content')
    @php
        $isLegacyAdmin = !empty($legacySessionUser['is_admin'] ?? false) || (bool) session('is_admin', false);
    @endphp

    <section class="hero">
        <span class="eyebrow">Cadastro de dependências</span>
        <h1>Dependências disponíveis no sistema.</h1>
        <p class="hero-copy">
            Esta tela permite consultar e manter dependências vinculadas às igrejas. O usuário consegue filtrar por
            administração e igreja, cadastrar novas entradas, editar descrições e remover apenas dependências que ainda não estejam ligadas a produtos.
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
                    <strong>Não foi possível salvar a dependência.</strong>
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
            <form method="GET" action="{{ route('migration.departments.index') }}" data-filter-autosubmit data-filter-message="Atualizando dependências…">
                <div class="filters-primary">
                    <label for="departments-admin-search">
                        Buscar administração
                        <input
                            id="departments-admin-search"
                            type="search"
                            placeholder="Digite para filtrar"
                            autocomplete="off"
                            aria-controls="departments-admin-select"
                            data-departments-admin-search
                        >
                    </label>
                    <label class="filters-principal">
                        Administração
                        <select id="departments-admin-select" name="administracao_id" data-departments-admin-select data-filter-server>
                            <option value="">Todas</option>
                            @foreach ($administrations as $administration)
                                <option value="{{ $administration->id }}" @selected($filters->administrationId === $administration->id)>
                                    #{{ $administration->id }} - {{ $administration->descricao }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <p id="departments-admin-search-status" class="helper" role="status" aria-live="polite" hidden data-departments-admin-status></p>

                    <label for="departments-church-search">
                        Buscar igreja
                        <input
                            id="departments-church-search"
                            type="search"
                            placeholder="Digite para filtrar"
                            autocomplete="off"
                            aria-controls="departments-church-select"
                            data-departments-church-search
                        >
                    </label>
                    <label class="filters-principal">
                        Igreja
                        <select id="departments-church-select" name="comum_id" data-departments-church-select data-filter-server>
                            <option value="">Todas</option>
                            @foreach ($churches as $church)
                                <option value="{{ $church->id }}" @selected($filters->comumId === $church->id)>
                                    {{ $church->codigo }} - {{ $church->descricao }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <p id="departments-church-search-status" class="helper" role="status" aria-live="polite" hidden data-departments-church-status></p>

                    <label class="filters-principal">
                        Estado (UF)
                        <select name="estado" id="departments-estado-select" data-filter-server>
                            <option value="">Todos os estados</option>
                            @foreach ($states as $stateCode => $stateLabel)
                                <option value="{{ $stateCode }}" @selected($filters->state === $stateCode)>
                                    {{ $stateCode }} - {{ $stateLabel }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="filters-query">
                        Descrição
                        <input type="text" name="busca" value="{{ $filters->search }}" placeholder="Nome da dependência" data-filter-search>
                    </label>

                    <div class="actions filters-actions">
                        <button class="btn primary" type="submit">Filtrar</button>
                        <a class="btn" href="{{ route('migration.departments.index') }}">Limpar</a>
                        <span class="helper" role="status" aria-live="polite" data-filter-status></span>
                    </div>
                </div>
            </form>
            @include('partials.filter-autosubmit')
            <script>
                (() => {
                    const search = document.querySelector('[data-departments-admin-search]');
                    const select = document.querySelector('[data-departments-admin-select]');
                    const status = document.querySelector('[data-departments-admin-status]');
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
                <h2>Lista de dependências</h2>
                <p>Os totais abaixo consideram apenas produtos ativos associados a cada dependência.</p>
            </div>
            <div class="inline-actions">
                @if ($isLegacyAdmin || !empty($legacyPermissions['departments.create'] ?? null))
                    <a class="btn primary" href="{{ route('migration.departments.create') }}">Nova dependência</a>
                @endif
            </div>
        </div>

        <div class="table-shell">
            @if ($departments->isEmpty())
                <div class="empty-state">Nenhuma dependência encontrada para os filtros atuais.</div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Dependência</th>
                            <th>Igreja</th>
                            <th>Produtos ativos</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($departments as $department)
                            <tr>
                                <td data-label="Dependência">{{ $department->descricao ?: 'Sem descrição' }}</td>
                                <td data-label="Igreja">
                                    {{ data_get($department, 'comum.codigo') ?: 'Nenhum' }}
                                    @if (data_get($department, 'comum.descricao'))
                                        <div class="table-note">{{ data_get($department, 'comum.descricao') }}</div>
                                    @endif
                                </td>
                                <td data-label="Produtos ativos">{{ $department->active_products_count }}</td>
                                <td data-label="Ações">
                                    <div class="inline-actions">
                                        @if ($isLegacyAdmin || !empty($legacyPermissions['products.view'] ?? null))
                                            <a class="btn" href="{{ route('migration.products.index', ['comum_id' => $department->comum_id, 'dependencia_id' => $department->id]) }}">
                                                Ver produtos
                                            </a>
                                        @endif
                                        @if ($isLegacyAdmin || !empty($legacyPermissions['departments.edit'] ?? null))
                                            <a class="btn" href="{{ route('migration.departments.edit', ['department' => $department->id]) }}">Editar</a>
                                        @endif
                                        @if ($isLegacyAdmin || !empty($legacyPermissions['departments.delete'] ?? null))
                                            <form method="POST" action="{{ route('migration.departments.destroy', ['department' => $department->id]) }}" data-confirm="Excluir esta dependência?">
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
                @include('partials.pagination', ['paginator' => $departments])
            @endif
        </div>
    </section>

    <script>
        (() => {
            const search = document.querySelector('[data-departments-church-search]');
            const select = document.querySelector('[data-departments-church-select]');
            const status = document.querySelector('[data-departments-church-status]');
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
                    status.textContent = 'Nenhuma igreja encontrada para \"' + search.value.trim() + '\".';
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
