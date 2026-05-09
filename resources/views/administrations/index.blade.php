@extends('layouts.migration')

@section('title', 'Administrações | ' . config('app.name'))

@section('content')
    @php
        $isLegacyAdmin = !empty($legacySessionUser['is_admin'] ?? false) || (bool) session('is_admin', false);
    @endphp

    <section class="hero">
        <span class="eyebrow">Cadastro administrativo</span>
        <h1>Administrações do sistema.</h1>
        <p class="hero-copy">
            Use este cadastro para selecionar a administração de cada importação e para vincular igrejas.
            Aqui ficam a identificação, o CNPJ, a descrição e a localização base.
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
                    <strong>Não foi possível salvar a administração.</strong>
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
            <form method="GET" action="{{ route('migration.administrations.index') }}">
                <div class="filters-primary">
                    <label class="filters-query">
                        Buscar por ID, descrição ou CNPJ
                        <input type="text" name="busca" value="{{ $filters->search }}" placeholder="1, Administração Central ou 12345678000190">
                    </label>

                    <div class="actions filters-actions">
                        <button class="btn primary" type="submit">Filtrar</button>
                        <a class="btn" href="{{ route('migration.administrations.index') }}">Limpar</a>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <section class="section">
        <div class="section-head">
            <div>
                <h2>Lista de administrações</h2>
                <p>{{ $totalAll }} registro(s) cadastrados.</p>
            </div>
            <div class="inline-actions">
                @if ($isLegacyAdmin || !empty($legacyPermissions['administrations.create'] ?? null))
                    <a class="btn primary" href="{{ route('migration.administrations.create') }}">Nova administração</a>
                @endif
            </div>
        </div>

        <div class="table-shell">
            @if ($administrations->isEmpty())
                <div class="empty-state">Nenhuma administração encontrada para os filtros atuais.</div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Descrição</th>
                            <th>CNPJ</th>
                            <th>Localização</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($administrations as $administration)
                            <tr>
                                <td data-label="ID" class="mono">{{ $administration->id }}</td>
                                <td data-label="Descrição">{{ $administration->descricao ?? 'Sem descrição' }}</td>
                                <td data-label="CNPJ" class="mono">{{ $administration->cnpj ?? 'Sem CNPJ' }}</td>
                                <td data-label="Localização">
                                    {{ trim(implode(' - ', array_filter([$administration->cidade ?? '', $administration->estado ?? '']))) ?: 'Sem localização' }}
                                </td>
                                <td data-label="Ações">
                                    <div class="inline-actions">
                                        @if ($isLegacyAdmin || !empty($legacyPermissions['administrations.edit'] ?? null))
                                            <a class="btn" href="{{ route('migration.administrations.edit', ['administration' => $administration->id]) }}">Editar</a>
                                        @endif
                                        @if ($isLegacyAdmin || !empty($legacyPermissions['administrations.delete'] ?? null))
                                            <form method="POST" action="{{ route('migration.administrations.destroy', ['administration' => $administration->id]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn" type="submit" onclick="return confirm('Excluir esta administração?');">
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
                @include('partials.pagination', ['paginator' => $administrations])
            @endif
        </div>
    </section>
@endsection
