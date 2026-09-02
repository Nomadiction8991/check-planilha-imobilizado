@extends('layouts.migration')

@section('title', 'Usuários | ' . config('app.name'))

@section('content')
    @php
        $isLegacyAdmin = !empty($legacySessionUser['is_admin'] ?? false) || (bool) session('is_admin', false);
    @endphp

    <section class="hero">
        <span class="eyebrow">Cadastro de usuários</span>
        <h1>Usuários vinculados a administrações.</h1>
        <p class="hero-copy">
            A listagem permite cadastro e manutenção de usuários, preservando as regras de senha, CPF, estado civil e
            dados do cônjuge. Cada usuário fica associado a uma administração, não a uma igreja específica.
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
            <form method="GET" action="{{ route('migration.users.index') }}">
                <div class="filters-primary">
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
                        <select id="users-admin-select" name="administracao_id" data-users-admin-select>
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
                        <select name="estado" id="users-estado-select">
                            <option value="">Todos os estados</option>
                            @foreach ($states as $stateCode => $stateLabel)
                                <option value="{{ $stateCode }}" @selected($filters->state === $stateCode)>
                                    {{ $stateCode }} - {{ $stateLabel }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="filters-query">
                        Buscar por nome ou email
                        <input type="text" name="busca" value="{{ $filters->search }}" placeholder="Nome ou email">
                    </label>

                    <div class="actions filters-actions">
                        <button class="btn primary" type="submit">Filtrar</button>
                        <a class="btn" href="{{ route('migration.users.index') }}">Limpar</a>
                    </div>
                </div>

                <div class="filters-advanced">
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
            <div class="inline-actions">
                @if ($isLegacyAdmin || !empty($legacyPermissions['users.create'] ?? null))
                    <a class="btn primary" href="{{ route('migration.users.create') }}">Novo usuário</a>
                @endif
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
                                <td data-label="Usuário">
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
                                <td data-label="Ações">
                                    <div class="inline-actions">
                                        @if ($isLegacyAdmin || !empty($legacyPermissions['users.permissions.manage'] ?? null))
                                            @unless ($isProtectedAdministrator)
                                                <a class="btn" href="{{ route('migration.users.permissions', ['user' => $user->id]) }}">Permissões</a>
                                            @endunless
                                        @endif
                                        @if ($isLegacyAdmin || !empty($legacyPermissions['users.edit'] ?? null))
                                            <a class="btn" href="{{ route('migration.users.edit', ['user' => $user->id]) }}">Editar</a>
                                        @endif
                                        @if (!$isProtectedAdministrator && ($isLegacyAdmin || !empty($legacyPermissions['users.delete'] ?? null)))
                                            <form method="POST" action="{{ route('migration.users.destroy', ['user' => $user->id]) }}" data-confirm="Excluir este usuário?">
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
                @include('partials.pagination', ['paginator' => $users])
            @endif
        </div>
    </section>
@endsection
