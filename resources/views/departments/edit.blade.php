@extends('layouts.migration')

@section('title', 'Editar Dependência | ' . config('app.name'))

@section('content')
    <section class="hero">
        <span class="eyebrow">Cadastro de dependências</span>
        <h1>Editar dependência.</h1>
        <p class="hero-copy">
            Atualize a descrição e o vínculo da dependência com a igreja. A exclusão permanece protegida quando houver
            produtos usando essa dependência.
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
            <form method="POST" action="{{ route('migration.departments.update', ['department' => $department->id]) }}" class="form-shell">
                @csrf
                @method('PUT')

                <div class="field-grid">
                    <label>
                        Igreja
                        <select name="comum_id" required>
                            <option value="">Selecione</option>
                            @foreach ($churches as $church)
                                <option
                                    value="{{ $church->id }}"
                                    @selected((int) old('comum_id', $department->comum_id) === (int) $church->id)
                                >
                                    {{ $church->codigo }} - {{ $church->descricao }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        Descrição
                        <input
                            type="text"
                            name="descricao"
                            value="{{ old('descricao', $department->descricao) }}"
                            maxlength="255"
                            placeholder="Ex.: SALAO"
                            required
                        >
                    </label>
                </div>

                <p class="field-note">O cadastro continua usando a mesma convenção: uma descrição única por igreja.</p>

                <div class="inline-actions">
                    <button class="btn primary" type="submit">Salvar alterações</button>
                    <a class="btn" href="{{ route('migration.departments.index') }}">Cancelar</a>
                </div>
            </form>
        </div>
    </section>
@endsection
