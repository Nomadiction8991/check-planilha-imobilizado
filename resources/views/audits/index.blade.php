@extends('layouts.migration')

@section('title', 'Auditoria | ' . config('app.name'))

@section('content')
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
        <div class="filters">
            <form method="GET" action="{{ route('migration.audits.index') }}">
                <div class="filters-primary">
                    <label class="filters-query">
                        Busca geral
                        <input
                            type="text"
                            name="busca"
                            value="{{ $filters['search'] }}"
                            placeholder="Usuário, ação, descrição, rota ou método"
                        >
                    </label>

                    <div class="actions filters-actions">
                        <button class="btn primary" type="submit">Filtrar</button>
                        <a class="btn" href="{{ route('migration.audits.index') }}">Limpar</a>
                    </div>
                </div>

                <div class="filters-advanced">
                    <label>
                        Módulo
                        <select name="modulo">
                            <option value="">Todos</option>
                            @foreach ($modules as $module)
                                <option value="{{ $module }}" @selected($filters['module'] === $module)>{{ $module }}</option>
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
                <h2>Auditoria do sistema</h2>
                <p>Escopo atual: {{ $scopeLabel }}. Aqui aparecem apenas ações concluídas com sucesso.</p>
            </div>
        </div>

        <div class="table-shell">
            @if ($audits->isEmpty())
                <div class="empty-state">Nenhum evento auditado encontrado para os filtros atuais.</div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Usuário</th>
                            <th>Módulo</th>
                            <th>Ação</th>
                            <th>Descrição</th>
                            <th>Origem</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($audits as $entry)
                            <tr>
                                <td data-label="Data">
                                    <div class="mono">
                                        {{ \Illuminate\Support\Carbon::createFromFormat('Y-m-d H:i:s', $entry->occurredAt)->format('d/m/Y H:i:s') }}
                                    </div>
                                </td>
                                <td data-label="Usuário">
                                    <div>{{ $entry->userName }}</div>
                                    @if ($entry->userEmail)
                                        <div class="table-note">{{ $entry->userEmail }}</div>
                                    @endif
                                    @if ($entry->administrationId !== null)
                                        <div class="table-note">Administração #{{ $entry->administrationId }}</div>
                                    @elseif ($entry->churchId !== null)
                                        <div class="table-note">Igreja #{{ $entry->churchId }}</div>
                                    @endif
                                </td>
                                <td data-label="Módulo">
                                    <span class="capsule dark">{{ $entry->module }}</span>
                                </td>
                                <td data-label="Ação">
                                    {{ $entry->action }}
                                </td>
                                <td data-label="Descrição">
                                    <div>{{ $entry->description }}</div>
                                    @if ($entry->routeName)
                                        <div class="table-note">{{ $entry->routeName }}</div>
                                    @endif
                                </td>
                                <td data-label="Origem">
                                    <div class="inline-actions">
                                        <span class="capsule accent">{{ $entry->method }}</span>
                                        <span class="capsule">{{ $entry->statusCode }}</span>
                                    </div>
                                    @if ($entry->path !== '')
                                        <div class="table-note">{{ $entry->path }}</div>
                                    @endif
                                    @if ($entry->ipAddress)
                                        <div class="table-note">{{ $entry->ipAddress }}</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @include('partials.pagination', ['paginator' => $audits])
            @endif
        </div>
    </section>
@endsection
