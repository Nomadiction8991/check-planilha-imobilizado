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
            igreja, cadastrar novas entradas, editar descrições e remover apenas dependências que ainda não estejam ligadas a produtos.
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
        <div class="filters">
            <form method="GET" action="{{ route('migration.departments.index') }}">
                <label>
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

                <label>
                    Descrição
                    <input type="text" name="busca" value="{{ $filters->search }}" placeholder="Nome da dependência">
                </label>

                <div class="actions">
                    <button class="btn primary" type="submit">Filtrar</button>
                    <a class="btn" href="{{ route('migration.departments.index') }}">Limpar</a>
                </div>
            </form>
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
                                    {{ data_get($department, 'comum.codigo') ?: 'n/a' }}
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
                                            <form method="POST" action="{{ route('migration.departments.destroy', ['department' => $department->id]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn" type="submit" onclick="return confirm('Excluir esta dependência?');">
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
@endsection
