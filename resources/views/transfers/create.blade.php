@extends('layouts.migration')

@section('title', 'Nova transferência | ' . config('app.name'))

@section('content')
    <section class="hero">
        <span class="eyebrow">Transferências</span>
        <h1>Nova transferência</h1>
        <p class="hero-copy">
            Os bens selecionados saem de {{ $igreja?->codigo }} - {{ $igreja?->descricao }}.
            Escolha primeiro a igreja de destino.
        </p>
    </section>

    @if (session('status'))
        <div class="flash-stack">
            <div class="flash {{ session('status_type', 'success') === 'error' ? 'error' : 'success' }}">
                <strong>{{ session('status') }}</strong>
            </div>
        </div>
    @endif

    <section class="section" aria-label="Igreja de destino">
        <form method="GET" action="{{ route('migration.transfers.create') }}">
            <label>
                Igreja de destino
                <select name="comum_destino_id" required>
                    <option value="">Selecione a igreja</option>
                    @foreach ($igrejasDeDestino as $opcao)
                        <option value="{{ $opcao->id }}" @selected((int) optional($destino)->id === (int) $opcao->id)>
                            {{ $opcao->codigo }} - {{ $opcao->descricao }}
                        </option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="btn">Escolher igreja</button>
        </form>
    </section>

    @if ($destino !== null)
        <section class="section" aria-label="Transferir bens">
            <form method="POST" action="{{ route('migration.transfers.store') }}">
                @csrf
                <input type="hidden" name="comum_destino_id" value="{{ $destino->id }}">

                <label>
                    Local do bem em {{ $destino->descricao }}
                    <select name="dependencia_destino_id" required>
                        <option value="">Selecione o local</option>
                        @foreach ($dependencias as $dependencia)
                            <option value="{{ $dependencia->id }}">{{ $dependencia->descricao }}</option>
                        @endforeach
                    </select>
                </label>

                <fieldset>
                    <legend>Bens que vão sair</legend>
                    @forelse ($bens as $bem)
                        <label>
                            <input type="checkbox" name="produto_ids[]" value="{{ $bem->id_produto }}">
                            <span class="mono">{{ $bem->codigo ?? 'sem código' }}</span>
                            {{ trim(($bem->editado ? $bem->editado_bem : $bem->bem) ?? '') }}
                        </label>
                    @empty
                        <p>Esta igreja não tem bens cadastrados.</p>
                    @endforelse
                </fieldset>

                <button type="submit" class="btn primary">Transferir bens selecionados</button>
            </form>
        </section>
    @endif
@endsection
