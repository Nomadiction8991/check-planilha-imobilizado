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

    <div class="cadastro-primary-action">
            <div class="inline-actions">
                @if ($isLegacyAdmin || !empty($legacyPermissions['administrations.create'] ?? null))
                    <a class="btn primary" href="{{ route('migration.administrations.create') }}">Nova administração</a>
                @endif
            </div>
    </div>

    <section class="section">
        <div data-sticky-filters>
            <form method="GET" action="{{ route('migration.administrations.index') }}" class="filters" data-filter-autosubmit data-filter-message="Atualizando administrações…">
                <div class="filters-bar">
                    <div class="filters-search">
                        <label for="administrations-search">Buscar por ID, descrição ou CNPJ</label>
                        <input id="administrations-search" type="search" name="busca" value="{{ $filters->search }}" placeholder="1, Administração Central ou 12345678000190" data-filter-search data-filter-server>
                    </div>
                    <button type="submit" class="btn primary"><span class="material-symbols-outlined" aria-hidden="true">search</span><span class="mobile-action-text">Buscar</span></button>
                    <button type="button" class="btn" data-filter-open aria-haspopup="dialog" aria-controls="filtros-administrations"><span class="material-symbols-outlined" aria-hidden="true">tune</span><span class="mobile-action-text">Filtros</span></button>
                </div>
                <span class="helper" role="status" aria-live="polite" data-filter-status></span>
                <dialog id="filtros-administrations" class="filters-dialog" aria-labelledby="filtros-administrations-titulo" data-filter-dialog>
                    <header class="filters-dialog-head">
                        <h2 id="filtros-administrations-titulo">Filtros</h2>
                        <button type="button" class="btn ghost" data-filter-close aria-label="Fechar filtros"><span class="material-symbols-outlined" aria-hidden="true">close</span></button>
                    </header>
                    <div class="filters-dialog-fields">
                    <label class="filters-principal">
                        Estado (UF)
                        <select name="estado" id="administrations-estado-select" data-filter-server>
                            <option value="">Todos os estados</option>
                            @foreach ($states ?? (array) config('brazil.states', []) as $stateCode => $stateLabel)
                                <option value="{{ $stateCode }}" @selected($filters->state === $stateCode)>
                                    {{ $stateCode }} - {{ $stateLabel }}
                                </option>
                            @endforeach
                        </select>
                    </label>


                    </div>
                    <footer class="filters-dialog-actions">
                        <a class="btn" href="{{ route('migration.administrations.index') }}"><span class="material-symbols-outlined" aria-hidden="true">filter_alt_off</span>Limpar</a>
                        <button type="submit" class="btn primary"><span class="material-symbols-outlined" aria-hidden="true">check</span>Aplicar filtros</button>
                    </footer>
                </dialog>
            </form>
            @include('partials.filter-autosubmit')
        </div>
    </section>

    <section class="section">
        <div class="section-head">
            <div>
                <h2>Lista de administrações</h2>
                <p>{{ $totalAll }} registro(s) cadastrados.</p>
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
                                <td data-label="Descrição" data-card-title>{{ $administration->descricao ?? 'Sem descrição' }}</td>
                                <td data-label="CNPJ" class="mono">{{ $administration->cnpj ?? 'Sem CNPJ' }}</td>
                                <td data-label="Localização">
                                    {{ trim(implode(' - ', array_filter([$administration->cidade ?? '', $administration->estado ?? '']))) ?: 'Sem localização' }}
                                </td>
                                <td data-label="Ações" data-card-actions>
                                    <div class="inline-actions">
                                        @if ($isLegacyAdmin || !empty($legacyPermissions['administrations.edit'] ?? null))
                                            <a aria-label="Editar" title="Editar" class="btn" href="{{ route('migration.administrations.edit', ['administration' => $administration->id]) }}"><span class="material-symbols-outlined mobile-action-icon" aria-hidden="true">edit</span><span class="mobile-action-text">Editar</span></a>
                                        @endif
                                        @if ($isLegacyAdmin || !empty($legacyPermissions['administrations.delete'] ?? null))
                                            <form method="POST" action="{{ route('migration.administrations.destroy', ['administration' => $administration->id]) }}" data-confirm="Excluir esta administração?">
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
                @include('partials.pagination', ['paginator' => $administrations])
            @endif
        </div>
    </section>

    <style>
        .cadastro-primary-action { margin-top: 20px; }
        .cadastro-form, .filters, .table-shell { font-size: 16px; line-height: 1.5; }
        .cadastro-field-error { display: block; color: var(--ui-danger); margin-top: 4px; }
        .btn.primary { background: var(--product-primary); border-color: var(--product-primary); color: var(--product-primary-text); }
        .btn.primary:hover { background: var(--product-primary-hover); border-color: var(--product-primary-hover); box-shadow: none; }
        .btn.danger { color: var(--ui-danger); }
        .btn:focus-visible, input:focus-visible, select:focus-visible { outline: 3px solid var(--ui-focus); outline-offset: 2px; }
        .btn:active { transform: translateY(1px); }
        .btn:disabled { opacity: .6; cursor: not-allowed; }
    </style>
@endsection
