@extends('layouts.migration')

@section('title', 'Transferência nº ' . $transferencia->numero_saida . ' | ' . config('app.name'))

@section('content')
    <section class="hero">
        <span class="eyebrow">Transferência</span>
        <h1>Saída nº {{ $transferencia->numero_saida }} · Entrada nº {{ $transferencia->numero_entrada }}</h1>
        <p class="hero-copy">
            De {{ $origem?->codigo }} - {{ $origem?->descricao }}
            para {{ $destino?->codigo }} - {{ $destino?->descricao }}
            @if ($local)
                , no local {{ $local->descricao }}
            @endif
            .
        </p>
    </section>

    @if (session('status'))
        <div class="flash-stack">
            <div class="flash {{ session('status_type', 'success') === 'error' ? 'error' : 'success' }}">
                <strong>{{ session('status') }}</strong>
            </div>
        </div>
    @endif

    <section class="section" aria-label="Formulários">
        <div class="section-head">
            <div>
                <h2>Formulários</h2>
                <p>Cada igreja imprime o seu formulário. A saída é da igreja de origem; a entrada é da igreja de destino.</p>
            </div>
        </div>

        @if ($igrejaAtual === (int) $transferencia->comum_origem_id)
            <a class="btn primary" href="{{ route('migration.transfers.form', [$transferencia, 'saida']) }}">Formulário de saída (14.3)</a>
        @endif

        @if ($igrejaAtual === (int) $transferencia->comum_destino_id)
            <a class="btn primary" href="{{ route('migration.transfers.form', [$transferencia, 'entrada']) }}">Formulário de entrada (14.2)</a>
        @endif

        @if ($igrejaAtual !== (int) $transferencia->comum_origem_id && $igrejaAtual !== (int) $transferencia->comum_destino_id)
            <p>Esta transferência não envolve a igreja atual.</p>
        @endif
    </section>

    <section class="section" aria-label="Bens transferidos">
        <div class="section-head">
            <div>
                <h2>Bens transferidos</h2>
            </div>
        </div>

        <div class="table-shell">
            <table>
                <thead>
                    <tr>
                        <th>Código antigo</th>
                        <th>Código novo</th>
                        <th>Descrição</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transferencia->itens as $item)
                        <tr>
                            <td data-label="Código antigo" class="mono">{{ $item->codigo_anterior ?? '—' }}</td>
                            <td data-label="Código novo" class="mono">{{ $item->codigo_novo ?? 'Pendente' }}</td>
                            <td data-label="Descrição">{{ $item->descricao }}</td>
                            <td data-label="Valor">{{ $item->valor !== null ? number_format((float) $item->valor, 2, ',', '.') : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
