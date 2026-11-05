@extends('layouts.migration')

@section('title', 'Novo Tipo de Bem | ' . config('app.name'))

@section('content')
    <section class="hero">
        <span class="eyebrow">Cadastro de bens</span>
        <h1>Novo tipo de bem.</h1>
        <p class="hero-copy">
            Este formulário grava diretamente na tabela de tipos de bens. O código continua sendo gerado
            automaticamente para preservar a regra atual do sistema.
        </p>
    </section>

    @if ($errors->any())
        <div class="flash-stack">
            <div class="flash error">
                <strong>Revise os dados informados.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <section class="section">
        <div class="table-shell">
            <form method="POST" action="{{ route('migration.asset-types.store') }}" class="form-shell cadastro-form">
                @csrf

                <div class="field-grid">
                    <label>
                        Administração
                        <select name="administracao_id" required>
                            <option value="">Selecione</option>
                            @foreach ($administrations as $administration)
                                <option value="{{ $administration->id }}" @selected((int) old('administracao_id', $selectedAdministrationId ?? '') === (int) $administration->id)>
                                    #{{ $administration->id }} - {{ $administration->descricao }}
                                </option>
                            @endforeach
                        </select>
                        @error('administracao_id') <span class="cadastro-field-error" role="alert">{{ $message }}</span> @enderror
                    </label>

                    <label>
                        Descrição
                        <input
                            type="text"
                            name="descricao"
                            value="{{ old('descricao') }}"
                            maxlength="255"
                            placeholder="Ex.: IMÓVEIS"
                            required
                        >
                        @error('descricao') <span class="cadastro-field-error" role="alert">{{ $message }}</span> @enderror
                    </label>
                </div>

                <p class="field-note">O código é sequencial e será gerado automaticamente ao salvar. Cada tipo de bem fica ligado a uma administração.</p>

                <div class="inline-actions">
                    <button class="btn primary" type="submit">Salvar tipo de bem</button>
                    <a class="btn" href="{{ route('migration.asset-types.index') }}">Cancelar</a>
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
