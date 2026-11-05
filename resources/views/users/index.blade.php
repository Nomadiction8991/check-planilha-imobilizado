@extends('layouts.migration')

@section('title', 'Usuários | ' . config('app.name'))

@section('content')
    @php
        $isLegacyAdmin = !empty($legacySessionUser['is_admin'] ?? false) || (bool) session('is_admin', false);
    @endphp

    <style>
        .users-page { color: var(--ui-text); font-size: 16px; line-height: 1.5; }
        .users-page h1 { font-size: 24px; line-height: 1.2; }
        .users-page .btn.primary { background: var(--product-primary); border-color: var(--product-primary); color: var(--product-primary-text); }
        .users-page .btn.primary:hover { background: var(--product-primary-hover); }
        .users-page .btn:focus-visible, .users-page input:focus-visible, .users-page select:focus-visible { outline: 3px solid var(--ui-focus); outline-offset: 2px; }
        .users-page .btn:active { transform: none; }
        .users-page .btn:disabled { opacity: .6; cursor: not-allowed; }
        .users-page .btn.danger { color: var(--ui-danger); }
        @media (min-width: 640px) { .users-page h1 { font-size: 30px; } }
    </style>
    <div class="users-page">
    <section class="hero">
        <span class="eyebrow">Cadastro de usuários</span>
        <h1>Usuários vinculados a administrações.</h1>
        <p class="hero-copy">
            A listagem permite cadastro e manutenção de usuários, preservando as regras de senha, CPF, estado civil e
            dados do cônjuge. Cada usuário fica associado a uma administração, não a uma igreja específica.
        </p>
    </section>

    @if ($isLegacyAdmin || !empty($legacyPermissions['users.create'] ?? null))
        <div class="section"><a class="btn primary" href="{{ route('migration.users.create') }}">Novo usuário</a></div>
    @endif

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
            <form method="GET" action="{{ route('migration.users.index') }}" class="filters" data-filter-autosubmit data-filter-message="Atualizando usuários…">
                <div class="filters-bar">
                    <div class="filters-search">
                        <label for="users-search">Buscar por nome ou email</label>
                        <input id="users-search" type="search" name="busca" value="{{ $filters->search }}" placeholder="Nome ou email" data-filter-search data-filter-server>
                    </div>
                    <button type="submit" class="btn primary"><span class="material-symbols-outlined" aria-hidden="true">search</span><span class="mobile-action-text">Buscar</span></button>
                    <button type="button" class="btn" data-filter-open aria-haspopup="dialog" aria-controls="filtros-users"><span class="material-symbols-outlined" aria-hidden="true">tune</span><span class="mobile-action-text">Filtros</span></button>
                </div>
                <span class="helper" role="status" aria-live="polite" data-filter-status></span>
                <dialog id="filtros-users" class="filters-dialog" aria-labelledby="filtros-users-titulo" data-filter-dialog>
                    <header class="filters-dialog-head">
                        <h2 id="filtros-users-titulo">Filtros</h2>
                        <button type="button" class="btn ghost" data-filter-close aria-label="Fechar filtros"><span class="material-symbols-outlined" aria-hidden="true">close</span></button>
                    </header>
                    <div class="filters-dialog-fields">
                    <label for="users-admin-search">
                        Buscar administração
                        <input
                            id="users-admin-search"
                            type="search"
                            placeholder="Digite para filtrar"
                            autocomplete="off"
                            aria-controls="users-admin-select"
                            data-users-admin-search
                        >
                    </label>
                    <label class="filters-principal">
                        Administração
                        <select id="users-admin-select" name="administracao_id" data-users-admin-select data-filter-server>
                            <option value="">Todas</option>
                            @foreach ($administrations as $administration)
                                <option value="{{ $administration->id }}" @selected($filters->administrationId === $administration->id)>
                                    #{{ $administration->id }} - {{ $administration->descricao }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <p id="users-admin-search-status" class="helper" role="status" aria-live="polite" hidden data-users-admin-status></p>

                    <label class="filters-principal">
                        Estado (UF)
                        <select name="estado" id="users-estado-select" data-filter-server>
                            <option value="">Todos os estados</option>
                            @foreach ($states as $stateCode => $stateLabel)
                                <option value="{{ $stateCode }}" @selected($filters->state === $stateCode)>
                                    {{ $stateCode }} - {{ $stateLabel }}
                                </option>
                            @endforeach
                        </select>
                    </label>


                    <label>
                        Status
                        <select name="status" data-filter-server>
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
                        <a class="btn" href="{{ route('migration.users.index') }}"><span class="material-symbols-outlined" aria-hidden="true">filter_alt_off</span>Limpar</a>
                        <button type="submit" class="btn primary"><span class="material-symbols-outlined" aria-hidden="true">check</span>Aplicar filtros</button>
                    </footer>
                </dialog>
            </form>
            @include('partials.filter-autosubmit')
            <script>
                (() => {
                    const search = document.querySelector('[data-users-admin-search]');
                    const select = document.querySelector('[data-users-admin-select]');
                    const status = document.querySelector('[data-users-admin-status]');
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
                <h2>Lista de usuários</h2>
                <p>Aqui o sistema assume consulta, cadastro, edição e exclusão do módulo principal, com o usuário administrador protegido.</p>
            </div>
        </div>

        <div class="table-shell">
            @if ($users->isEmpty())
                <div class="empty-state">Nenhum usuário encontrado para os filtros atuais.</div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Usuário</th>
                            <th>Administração</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            @php
                                $isProtectedAdministrator = method_exists($user, 'isProtectedAdministratorAccount')
                                    ? $user->isProtectedAdministratorAccount()
                                    : false;
                            @endphp
                            <tr>
                                <td data-label="Usuário" data-card-title>
                                    <div>{{ $user->nome ?: 'Sem nome' }}</div>
                                    <div class="table-note">{{ $user->email ?: 'Sem email' }}</div>
                                </td>
                                <td data-label="Administração">
                                    {{ data_get($user, 'administracao.id') ? '#' . data_get($user, 'administracao.id') : 'Nenhum' }}
                                    @if (data_get($user, 'administracao.descricao'))
                                        <div class="table-note">{{ data_get($user, 'administracao.descricao') }}</div>
                                    @endif
                                </td>
                                <td data-label="Status">
                                    @if ((int) $user->ativo === 1)
                                        <span class="capsule accent">Ativo</span>
                                    @else
                                        <span class="capsule warn">Inativo</span>
                                    @endif
                                </td>
                                <td data-label="Ações" data-card-actions>
                                    <div class="inline-actions">
                                        @if ($isLegacyAdmin || !empty($legacyPermissions['users.permissions.manage'] ?? null))
                                            @unless ($isProtectedAdministrator)
                                                <a class="btn" href="{{ route('migration.users.permissions', ['user' => $user->id]) }}">Permissões</a>
                                            @endunless
                                        @endif
                                        @if ($isLegacyAdmin || !empty($legacyPermissions['users.edit'] ?? null))
                                            <a aria-label="Editar" title="Editar" class="btn" href="{{ route('migration.users.edit', ['user' => $user->id]) }}"><span class="material-symbols-outlined mobile-action-icon" aria-hidden="true">edit</span><span class="mobile-action-text">Editar</span></a>
                                        @endif
                                        @if (!$isProtectedAdministrator && ($isLegacyAdmin || !empty($legacyPermissions['users.delete'] ?? null)))
                                            <form method="POST" action="{{ route('migration.users.destroy', ['user' => $user->id]) }}" data-confirm="Excluir este usuário?">
                                                @csrf
                                                @method('DELETE')
                                                <button aria-label="Excluir" title="Excluir" class="btn danger" type="submit"><span class="material-symbols-outlined mobile-action-icon" aria-hidden="true">delete</span><span class="mobile-action-text">Excluir</span></button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @include('partials.pagination', ['paginator' => $users])
            @endif
        </div>
    </section>
    </div>
@endsection
