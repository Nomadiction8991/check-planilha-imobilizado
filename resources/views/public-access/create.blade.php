@extends('layouts.migration')

@section('title', 'Assinar Documentos - Acesso Público')

@section('content')
    <section class="hero" style="max-width: 720px; margin-inline: auto;">
        <span class="eyebrow">Acesso público</span>
        <h1>Selecione sua igreja para continuar.</h1>
        <p class="hero-copy">
            Use esta tela para iniciar o atendimento público e acessar apenas os itens vinculados à igreja escolhida.
        </p>
    </section>

    @if (session('status'))
        <div class="flash-stack" style="max-width: 720px; margin-inline: auto;">
            <div class="flash {{ session('status_type', 'info') === 'error' ? 'error' : 'success' }}">
                <strong>{{ session('status') }}</strong>
            </div>
        </div>
    @endif

    <section class="section" style="max-width: 720px; margin-inline: auto;">
        <div class="table-shell">
            <form method="POST" action="{{ route('public.access.store') }}" class="form-shell">
                @csrf

                <div class="field-grid">
                    <label>
                        Igreja
                        <select id="comum_id" name="comum_id" required>
                            <option value="">Selecione</option>
                            @foreach ($churches as $church)
                                <option value="{{ $church->id }}">{{ $church->descricao }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                @error('comum_id')
                    <div class="flash error">
                        <strong>{{ $message }}</strong>
                    </div>
                @enderror

                <div class="inline-actions">
                    <button type="submit" class="btn primary">Continuar</button>
                    <a href="{{ route('migration.login') }}" class="btn">Voltar ao login</a>
                </div>
            </form>
        </div>
    </section>
@endsection
