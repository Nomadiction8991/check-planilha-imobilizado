@extends('layouts.migration')

@section('title', 'Nova Administração | ' . config('app.name'))

@section('content')
    <section class="hero">
        <span class="eyebrow">Cadastro administrativo</span>
        <h1>Nova administração.</h1>
        <p class="hero-copy">
            Cadastre a administração que depois será selecionada no envio da planilha e vinculada às igrejas.
            O CNPJ será usado nos relatórios da igreja vinculada.
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
            <form method="POST" action="{{ route('migration.administrations.store') }}" class="form-shell cadastro-form">
                @csrf

                <div class="field-grid">
                    <label>
                        Descrição
                        <input
                            type="text"
                            name="descricao"
                            value="{{ old('descricao') }}"
                            maxlength="255"
                            placeholder="Ex.: Administração Central"
                            required
                        >
                        @error('descricao') <span class="cadastro-field-error" role="alert">{{ $message }}</span> @enderror
                    </label>

                    <label>
                        CNPJ
                        <input
                            type="text"
                            name="cnpj"
                            value="{{ old('cnpj') }}"
                            maxlength="18"
                            data-mask="cnpj"
                            inputmode="numeric"
                            placeholder="00.000.000/0000-00"
                            required
                        >
                        @error('cnpj') <span class="cadastro-field-error" role="alert">{{ $message }}</span> @enderror
                    </label>

                    <label>
                        Estado
                        <select name="estado" id="administration-estado" required>
                            <option value="">Selecione o estado</option>
                            @foreach ($states ?? (array) config('brazil.states', []) as $stateCode => $stateLabel)
                                <option value="{{ $stateCode }}" @selected(old('estado') === $stateCode)>
                                    {{ $stateCode }} - {{ $stateLabel }}
                                </option>
                            @endforeach
                        </select>
                        @error('estado') <span class="cadastro-field-error" role="alert">{{ $message }}</span> @enderror
                    </label>

                    <label>
                        Cidade
                        <select name="cidade" id="administration-cidade" required disabled data-selected-city="{{ old('cidade') }}">
                            <option value="">Selecione um estado primeiro</option>
                        </select>
                        @error('cidade') <span class="cadastro-field-error" role="alert">{{ $message }}</span> @enderror
                    </label>
                </div>

                <p class="field-note">A descrição será usada no seletor da importação. Estado e cidade ajudam a identificar a administração vinculada.</p>

                <div class="inline-actions">
                    <button class="btn primary" type="submit">Salvar administração</button>
                    <a class="btn" href="{{ route('migration.administrations.index') }}">Cancelar</a>
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
