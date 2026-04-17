@extends('layouts.migration')

@section('title', 'Editar Administração | ' . config('app.name'))

@section('content')
    <section class="hero">
        <span class="eyebrow">Cadastro administrativo</span>
        <h1>Editar administração.</h1>
        <p class="hero-copy">
            Atualize a descrição usada no vínculo com as importações já cadastradas.
        </p>
    </section>

    @if (session('status') || $errors->any())
        <div class="flash-stack">
            @if (session('status'))
                <div class="flash {{ session('status_type', 'success') === 'error' ? 'error' : 'success' }}">
                    <strong>{{ session('status') }}</strong>
                </div>
            @endif

            @if ($errors->any())
                <div class="flash error">
                    <strong>Revise os dados informados.</strong>
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
        <div class="table-shell">
            <form method="POST" action="{{ route('migration.administrations.update', ['administration' => $administration->id]) }}" class="form-shell">
                @csrf
                @method('PUT')

                <div class="field-grid">
                    <label>
                        Descrição
                        <input
                            type="text"
                            name="descricao"
                            value="{{ old('descricao', $administration->descricao) }}"
                            maxlength="255"
                            placeholder="Ex.: Administração Central"
                            required
                        >
                    </label>
                </div>

                <p class="field-note">A alteração reflete nos envios novos e no histórico já vinculado.</p>

                <div class="inline-actions">
                    <button class="btn primary" type="submit">Salvar alterações</button>
                    <a class="btn" href="{{ route('migration.administrations.index') }}">Cancelar</a>
                </div>
            </form>
        </div>
    </section>
@endsection
