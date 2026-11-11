@extends('layouts.migration')

@section('title', 'Transferências | ' . config('app.name'))

@section('content')
    <section class="hero">
        <span class="eyebrow">Transferências</span>
        <h1>Bens entre igrejas</h1>
        <p class="hero-copy">
            Igreja atual: {{ $igreja?->codigo }} - {{ $igreja?->descricao }}.
            Quando um bem muda de igreja, ele perde o código e fica pendente até a igreja de destino informar o novo.
        </p>
    </section>

    @if (session('status'))
        <div class="flash-stack">
            <div class="flash {{ session('status_type', 'success') === 'error' ? 'error' : 'success' }}">
                <strong>{{ session('status') }}</strong>
            </div>
        </div>
    @endif

    <section class="section">
        <p>Para transferir um bem, abra a lista de produtos e use o botão Transferir.</p>
        <a class="btn" href="{{ route('migration.products.index') }}">Ir para a lista de produtos</a>
    </section>

    <section class="section" aria-label="Bens pendentes de código">
        <div class="section-head">
            <div>
                <h2>Pendentes de código</h2>
                <p>Bens que chegaram por transferência. Informe o código novo para enviar a etiqueta para impressão.</p>
            </div>
        </div>

        <div class="table-shell">
            @if ($pendentes->isEmpty())
                <div class="empty-state">Nenhum bem pendente de código.</div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Descrição</th>
                            <th>Código novo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pendentes as $bem)
                            <tr>
                                <td data-label="Descrição" data-card-title>
                                    {{ trim(($bem->editado ? $bem->editado_bem : $bem->bem) ?? '') }}
                                </td>
                                <td data-label="Código novo">
                                    <form method="POST" action="{{ route('migration.transfers.code') }}">
                                        @csrf
                                        <input type="hidden" name="produto_id" value="{{ $bem->id_produto }}">
                                        <label>
                                            Código
                                            <input type="text" name="codigo" maxlength="50" required>
                                        </label>
                                        <button aria-label="Salvar código" title="Salvar código" type="submit" class="btn primary"><span class="material-symbols-outlined mobile-action-icon" aria-hidden="true">save</span><span class="mobile-action-text">Salvar código</span></button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </section>

    <section class="section" aria-label="Histórico de transferências">
        <div class="section-head">
            <div>
                <h2>Histórico</h2>
                <p>Bens que a igreja enviou (saída) ou recebeu (entrada).</p>
            </div>
        </div>

        <div class="table-shell">
            @if ($historico === [])
                <div class="empty-state">Nenhuma transferência nesta igreja.</div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Nº</th>
                            <th>Código antigo</th>
                            <th>Código novo</th>
                            <th>Descrição</th>
                            <th>Origem → destino</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($historico as $linha)
                            <tr>
                                <td data-label="Data">
                                    <a href="{{ route('migration.transfers.show', $linha['transferencia_id']) }}">{{ $linha['data'] }}</a>
                                </td>
                                <td data-label="Tipo">{{ $linha['tipo'] === 'saida' ? 'Saída' : 'Entrada' }}</td>
                                <td data-label="Nº">{{ $linha['numero'] }}</td>
                                <td data-label="Código antigo" class="mono">{{ $linha['codigo_anterior'] ?? '—' }}</td>
                                <td data-label="Código novo" class="mono">{{ $linha['codigo_novo'] ?? 'Pendente' }}</td>
                                <td data-label="Descrição">{{ $linha['descricao'] }}</td>
                                <td data-label="Origem → destino">{{ $linha['origem'] }} → {{ $linha['destino'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </section>
@endsection
