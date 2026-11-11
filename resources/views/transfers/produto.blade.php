@extends('layouts.migration')

@section('title', 'Transferir bem | ' . config('app.name'))

@section('content')
    <section class="hero">
        <span class="eyebrow">Transferências</span>
        <h1>Transferir bem</h1>
        <p class="hero-copy">Escolha a igreja e o local de destino. Ao transferir, o bem perde o código atual e fica pendente até a igreja de destino informar o código novo.</p>
    </section>

    @if (session('status'))
        <div class="flash-stack">
            <div class="flash {{ session('status_type', 'success') === 'error' ? 'error' : 'success' }}">
                <strong>{{ session('status') }}</strong>
            </div>
        </div>
    @endif

    <section class="section transfer-product">
        <div class="section-head"><h2>Bem a transferir</h2></div>
        <p><span class="mono">{{ $bem->codigo ?: 'sem código' }}</span> — {{ \App\Support\LegacyProductNameSupport::formatCurrentName($bem) }}</p>
        <p>Igreja atual: {{ $origem->codigo }} - {{ $origem->descricao }}</p>
    </section>

    <section class="section transfer-product" aria-label="Igreja de destino">
        @if ($igrejasDeDestino->isEmpty())
            <p>Não há outra igreja disponível para transferir este bem.</p>
        @else
            <form method="GET" action="{{ route('migration.products.transfer', ['product' => $bem->id_produto]) }}">
                <label for="comum_destino_id">Igreja de destino</label>
                <select id="comum_destino_id" name="comum_destino_id" required>
                    <option value="">Selecione a igreja</option>
                    @foreach ($igrejasDeDestino as $opcao)
                        <option value="{{ $opcao->id }}" @selected((int) optional($destino)->id === (int) $opcao->id)>{{ $opcao->codigo }} - {{ $opcao->descricao }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn">Continuar</button>
            </form>
        @endif
    </section>

    @if ($destino !== null)
        <section class="section transfer-product" aria-label="Local de destino">
            @if ($dependencias->isEmpty())
                <p>A igreja de destino ainda não tem locais cadastrados.</p>
            @else
                <form method="POST" action="{{ route('migration.products.transfer.store', ['product' => $bem->id_produto]) }}">
                    @csrf
                    <input type="hidden" name="comum_destino_id" value="{{ $destino->id }}">
                    <label for="dependencia_destino_id">Local do bem em {{ $destino->descricao }}</label>
                    <select id="dependencia_destino_id" name="dependencia_destino_id" required @error('dependencia_destino_id') aria-invalid="true" aria-describedby="dependencia-error" @enderror>
                        <option value="">Selecione o local</option>
                        @foreach ($dependencias as $dependencia)
                            <option value="{{ $dependencia->id }}" @selected(old('dependencia_destino_id') == $dependencia->id)>{{ $dependencia->descricao }}</option>
                        @endforeach
                    </select>
                    @error('dependencia_destino_id') <span id="dependencia-error" class="cadastro-field-error" role="alert">{{ $message }}</span> @enderror
                    <button type="submit" class="btn primary">Transferir bem</button>
                </form>
            @endif
        </section>
    @endif

    <section class="section transfer-product">
        <a class="btn" href="{{ route('migration.products.index') }}">Voltar para a lista de produtos</a>
    </section>

    <style>
        .transfer-product form { display: grid; gap: 12px; }
        .transfer-product select, .transfer-product .btn { min-height: 44px; width: 100%; }
        @media (min-width: 640px) {
            .transfer-product form { max-width: 520px; }
            .transfer-product .btn { width: fit-content; }
        }
    </style>
@endsection
