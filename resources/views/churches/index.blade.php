@extends('layouts.migration')

@section('title', 'Igrejas | ' . config('app.name'))

@section('content')
    @php
        $isLegacyAdmin = !empty($legacySessionUser['is_admin'] ?? false) || (bool) session('is_admin', false);
    @endphp

    <section class="hero">
        <span class="eyebrow">Consulta e edição</span>
        <h1>Igrejas cadastradas no sistema.</h1>
        <p class="hero-copy">
            Esta tela permite filtrar, paginar, contar produtos ativos por igreja e acessar a área de produtos ou a
            edição do cadastro com rapidez.
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
        <div class="filters">
            <form method="GET" action="{{ route('migration.churches.index') }}">
                <label>
                    Buscar por código ou descrição
                    <input type="text" name="busca" value="{{ $filters->search }}" placeholder="BR 12-3456 ou descrição">
                </label>
                <div class="actions">
                    <button class="btn primary" type="submit">Filtrar</button>
                    <a class="btn" href="{{ route('migration.churches.index') }}">Limpar</a>
                </div>
            </form>
        </div>
    </section>

    <section class="section">
        <div class="section-head">
            <div>
                <h2>Lista de igrejas</h2>
                <p>Contagem de produtos considera apenas registros ativos.</p>
            </div>
        </div>

        <div class="table-shell">
            @if ($churches->isEmpty())
                <div class="empty-state">Nenhuma igreja encontrada para o filtro informado.</div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descrição</th>
                            <th>Cidade</th>
                            <th>Setor</th>
                            <th>Produtos ativos</th>
                            @unless (session('public_acesso'))
                                <th>Ações</th>
                            @endunless
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($churches as $church)
                            <tr>
                                <td data-label="Código" class="mono">{{ $church->codigo ?: 'n/a' }}</td>
                                <td data-label="Descrição">{{ $church->descricao ?: 'Sem descrição' }}</td>
                                <td data-label="Cidade">
                                    {{ trim(implode(' - ', array_filter([$church->cidade, $church->estado]))) ?: 'n/a' }}
                                </td>
                                <td data-label="Setor">{{ $church->setor ?: 'n/a' }}</td>
                                <td data-label="Produtos ativos">{{ $church->active_products_count }}</td>
                                @unless (session('public_acesso'))
                                    <td data-label="Ações">
                                        <div class="inline-actions">
                                            @if ($isLegacyAdmin || !empty($legacyPermissions['products.view'] ?? null))
                                                <a class="btn" href="{{ route('migration.products.index', ['comum_id' => $church->id]) }}">Produtos</a>
                                            @endif
                                            @if ($isLegacyAdmin || !empty($legacyPermissions['churches.edit'] ?? null))
                                                <a class="btn" href="{{ route('migration.churches.edit', ['church' => $church->id]) }}">Editar</a>
                                            @endif
                                            @if ($isLegacyAdmin || !empty($legacyPermissions['churches.delete'] ?? null))
                                                <form method="POST" action="{{ route('migration.churches.delete-products') }}" class="delete-products-form" data-products-count-url="{{ route('migration.churches.products-count', ['comum_id' => $church->id]) }}" data-church-name="{{ $church->descricao ?: 'Sem descrição' }}">
                                                    @csrf
                                                    <input type="hidden" name="comum_id" value="{{ $church->id }}">
                                                    <button class="btn danger" type="submit">Excluir produtos</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                @endunless
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @include('partials.pagination', ['paginator' => $churches])
            @endif
        </div>
    </section>

    <script>
        (() => {
            document.querySelectorAll('.delete-products-form').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const countUrl = form.dataset.productsCountUrl;
                    const churchName = form.dataset.churchName || 'igreja selecionada';
                    let message = `Excluir todos os produtos de ${churchName}?`;

                    try {
                        const response = await fetch(countUrl, {
                            headers: { Accept: 'application/json' },
                        });

                        if (response.ok) {
                            const payload = await response.json();
                            const count = Number(payload.count || 0);
                            message = count > 0
                                ? `Excluir ${count} produto(s) de ${churchName}?`
                                : `Nenhum produto cadastrado em ${churchName}. Confirmar mesmo assim?`;
                        }
                    } catch (error) {
                    }

                    if (window.confirm(message)) {
                        form.submit();
                    }
                });
            });
        })();
    </script>
@endsection
