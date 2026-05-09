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
                    <label class="filters-principal">
                        Administração
                        <select name="administracao_id">
                            <option value="">Todas</option>
                            @foreach ($administrations as $administration)
                                <option value="{{ $administration->id }}" @selected($filters->administrationId === $administration->id)>
                                    #{{ $administration->id }} - {{ $administration->descricao }}
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
                                    @if ($isProtectedAdministrator)
                                        <span class="capsule dark">Protegido</span>
                                    @else
                                        <div class="inline-actions">
                                            @if ($isLegacyAdmin || !empty($legacyPermissions['users.permissions.manage'] ?? null))
                                                <a class="btn" href="{{ route('migration.users.permissions', ['user' => $user->id]) }}">Permissões</a>
                                            @endif
                                            @if ($isLegacyAdmin || !empty($legacyPermissions['users.edit'] ?? null))
                                                <a class="btn" href="{{ route('migration.users.edit', ['user' => $user->id]) }}">Editar</a>
                                            @endif
                                            @if ($isLegacyAdmin || !empty($legacyPermissions['users.delete'] ?? null))
                                                <form method="POST" action="{{ route('migration.users.destroy', ['user' => $user->id]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn" type="submit" onclick="return confirm('Excluir este usuário?');">
                                                        Excluir
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @endif
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
