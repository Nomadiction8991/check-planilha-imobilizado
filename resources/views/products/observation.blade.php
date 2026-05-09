@extends('layouts.migration')

@section('title', 'Observação do Produto | ' . config('app.name'))

@section('content')
    @php
        $description = \App\Support\LegacyProductNameSupport::formatCurrentName($product);
        $search = trim((string) ($filters['busca'] ?? $filters['nome'] ?? $filters['codigo'] ?? ''));
        $returnQuery = array_filter([
            'comum_id' => $churchId,
            'pagina' => $filters['pagina'] ?? null,
            'busca' => $search !== '' ? $search : null,
            'dependencia_id' => $filters['dependencia_id'] ?? null,
            'status' => $filters['status'] ?? null,
            'somente_novos' => !empty($filters['somente_novos']) ? 1 : null,
        ], static fn ($value) => $value !== null && $value !== '');
    @endphp

    <section class="hero">
        <span class="eyebrow">Observação do produto</span>
        <h1>Observação do produto.</h1>
        <p class="hero-copy">
            Esta tela complementa o cadastro com observações sem mudar o fluxo operacional das listagens.
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
        <div class="filters">
            <form method="POST" action="{{ route('migration.compat.products.observation.store') }}">
                @csrf
                <input type="hidden" name="produto_id" value="{{ $product->id_produto }}">
                <input type="hidden" name="comum_id" value="{{ $churchId }}">
                <input type="hidden" name="pagina" value="{{ $filters['pagina'] ?? 1 }}">
                <input type="hidden" name="busca" value="{{ $search }}">
                <input type="hidden" name="dependencia_id" value="{{ $filters['dependencia_id'] ?? '' }}">
                <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">
                <input type="hidden" name="somente_novos" value="{{ !empty($filters['somente_novos']) ? 1 : 0 }}">

                <label style="grid-column: 1 / -1;">
                    Observações
                    <textarea name="observacoes" rows="8">{{ old('observacoes', $product->observacao ?? '') }}</textarea>
                </label>

                <div class="actions">
                    <button class="btn primary" type="submit">Salvar observação</button>
                    <a class="btn" href="{{ route('migration.products.index', $returnQuery) }}">Voltar</a>
                </div>
            </form>
        </div>
    </section>
@endsection
