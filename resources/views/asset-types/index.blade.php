@extends('layouts.migration')

@section('title', 'Tipos de Bem | ' . config('app.name'))

@section('content')
    @php
        $isLegacyAdmin = !empty($legacySessionUser['is_admin'] ?? false) || (bool) session('is_admin', false);
    @endphp

    <section class="hero">
        <span class="eyebrow">Cadastro de bens</span>
        <h1>Tipos de bem disponíveis no sistema.</h1>
        <p class="hero-copy">
            O catálogo de tipos de bem está navegável e editável neste sistema. A página permite localizar códigos,
            medir uso real por produtos ativos e operar o cadastro com segurança.
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
                    <strong>Não foi possível salvar o tipo de bem.</strong>
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
            <form method="GET" action="{{ route('migration.asset-types.index') }}">
                <div class="filters-primary">
                    <label class="filters-query">
                        Buscar por código ou descrição
                        <input type="text" name="busca" value="{{ $filters->search }}" placeholder="4 ou CADEIRA">
                    </label>

                    <div class="actions filters-actions">
                        <button class="btn primary" type="submit">Filtrar</button>
                        <a class="btn" href="{{ route('migration.asset-types.index') }}">Limpar</a>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <section class="section">
        <div class="section-head">
            <div>
                <h2>Lista de tipos de bem</h2>
                <p>A contagem considera apenas produtos ativos vinculados a cada tipo.</p>
            </div>
            <div class="inline-actions">
                @if ($isLegacyAdmin || !empty($legacyPermissions['asset-types.create'] ?? null))
                    <a class="btn primary" href="{{ route('migration.asset-types.create') }}">Novo tipo de bem</a>
                @endif
            </div>
        </div>

        <div class="table-shell">
            @if ($assetTypes->isEmpty())
                <div class="empty-state">Nenhum tipo de bem encontrado para os filtros atuais.</div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descrição</th>
                            <th>Produtos ativos</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($assetTypes as $assetType)
                            <tr>
                                <td data-label="Código" class="mono">{{ $assetType->codigo }}</td>
                                <td data-label="Descrição">{{ $assetType->descricao ?: 'Sem descrição' }}</td>
                                <td data-label="Produtos ativos">{{ $assetType->active_products_count }}</td>
                                <td data-label="Ações">
                                    <div class="inline-actions">
                                        @if ($isLegacyAdmin || !empty($legacyPermissions['products.view'] ?? null))
                                            <a class="btn" href="{{ route('migration.products.index', ['tipo_bem_id' => $assetType->id]) }}">Ver produtos</a>
                                        @endif
                                        @if ($isLegacyAdmin || !empty($legacyPermissions['asset-types.edit'] ?? null))
                                            <a class="btn" href="{{ route('migration.asset-types.edit', ['assetType' => $assetType->id]) }}">Editar</a>
                                        @endif
                                        @if ($isLegacyAdmin || !empty($legacyPermissions['asset-types.delete'] ?? null))
                                            <form method="POST" action="{{ route('migration.asset-types.destroy', ['assetType' => $assetType->id]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn" type="submit" onclick="return confirm('Excluir este tipo de bem?');">
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
                @include('partials.pagination', ['paginator' => $assetTypes])
            @endif
        </div>
    </section>
@endsection
