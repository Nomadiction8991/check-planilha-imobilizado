@extends('layouts.migration')

@section('title', 'Nova Dependência | ' . config('app.name'))

@section('content')
    <section class="hero">
        <span class="eyebrow">Cadastro de dependências</span>
        <h1>Nova dependência.</h1>
        <p class="hero-copy">
            Este formulário salva dependências vinculadas às igrejas. A igreja precisa ser escolhida explicitamente
            para manter o vínculo correto.
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
            <form method="POST" action="{{ route('migration.departments.store') }}" class="form-shell cadastro-form">
                @csrf

                <div class="field-grid">
                    <label>
                        Igreja
                        <select name="comum_id" required>
                            <option value="">Selecione</option>
                            @foreach ($churches as $church)
                                <option value="{{ $church->id }}" @selected((int) old('comum_id') === (int) $church->id)>
                                    {{ $church->codigo }} - {{ $church->descricao }}
                                </option>
                            @endforeach
                        </select>
                        @error('comum_id') <span class="cadastro-field-error" role="alert">{{ $message }}</span> @enderror
                    </label>

                    <label>
                        Descrição
                        <input
                            type="text"
                            name="descricao"
                            value="{{ old('descricao') }}"
                            maxlength="255"
                            placeholder="Ex.: SALAO"
                            required
                        >
                        @error('descricao') <span class="cadastro-field-error" role="alert">{{ $message }}</span> @enderror
                    </label>
                </div>

                <p class="field-note">A descrição será normalizada em caixa alta ao salvar.</p>

                <div class="inline-actions">
                    <button class="btn primary" type="submit">Salvar dependência</button>
                    <a class="btn" href="{{ route('migration.departments.index') }}">Cancelar</a>
                </div>
            </form>
        </div>
    </section>

    <style>
        .cadastro-primary-action { margin-top: 20px; }
        .cadastro-form, .filters, .table-shell { font-size: 16px; line-height: 1.5; }
        .cadastro-field-error { display: block; color: var(--ui-danger); margin-top: 4px; }
        .btn.primary { background: var(--product-primary); border-color: var(--product-primary); color: white; }
        .btn.primary:hover { background: var(--product-primary-hover); border-color: var(--product-primary-hover); box-shadow: none; }
        .btn.danger { color: var(--ui-danger); }
        .btn:focus-visible, input:focus-visible, select:focus-visible { outline: 3px solid var(--ui-focus); outline-offset: 2px; }
        .btn:active { transform: translateY(1px); }
        .btn:disabled { opacity: .6; cursor: not-allowed; }
    </style>
@endsection
