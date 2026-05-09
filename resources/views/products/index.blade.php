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
                    <label class="filters-principal">
                        Igreja
                        <select name="comum_id">
                            <option value="">Todas</option>
                            @foreach ($churches as $church)
                                <option value="{{ $church->id }}" @selected($filters->comumId === $church->id)>
                                    {{ $church->codigo }} - {{ $church->descricao }}
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
                                $description = trim(implode(' ', array_filter([
                                    $product->bem,
                                    $product->complemento,
                                ])));
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
                                        <a class="btn" href="{{ route('migration.products.edit', ['product' => $product->id_produto]) }}">
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
@endsection
