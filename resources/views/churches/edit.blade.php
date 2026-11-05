@extends('layouts.migration')

@section('title', 'Editar Igreja | ' . config('app.name'))

@section('content')
    <style>
        .church-page-header h1 { font-size: 24px; line-height: 1.25; font-weight: 700; }
        .church-page-header .hero-copy { font-size: 16px; }
        .church-field-error { color: var(--ui-danger); font-weight: 600; }
        .church-edit input, .church-edit select, .church-edit .btn { min-height: 44px; font-size: 16px; }
        .church-edit label { font-size: 16px; }
        .church-edit :is(input, select, button, a):focus-visible { outline: 3px solid var(--ui-focus); outline-offset: 2px; }
        @media (min-width: 640px) { .church-page-header h1 { font-size: 32px; } }
    </style>
    <section class="hero church-page-header">
        <span class="eyebrow">Cadastro de igrejas</span>
        <h1>Editar igreja.</h1>
        <p class="hero-copy">Atualize os dados da igreja e sua administração vinculada.</p>
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

    <section class="section church-edit">
        <div class="table-shell">
            <form id="church-edit-form" method="POST" action="{{ route('migration.churches.update', ['church' => $church->id]) }}" class="form-shell">
                @csrf
                @method('PUT')

                <div class="field-grid">
                    <label>
                        Administração vinculada
                        <select name="administracao_id" required @error('administracao_id') aria-invalid="true" aria-describedby="church-administracao-error" @enderror>
                            <option value="">Selecione</option>
                            @foreach ($administrations as $administration)
                                <option value="{{ $administration->id }}" @selected((int) old('administracao_id', $church->administracao_id ?? 0) === (int) $administration->id)>
                                    #{{ $administration->id }} - {{ $administration->descricao }}
                                </option>
                            @endforeach
                        </select>
                        @error('administracao_id')<span class="church-field-error" id="church-administracao-error">{{ $message }}</span>@enderror
                    </label>

                    <label>
                        Código
                        <input type="text" value="{{ $church->codigo }}" readonly>
                    </label>

                    <label>
                        CNPJ
                        <input
                            id="church-cnpj"
                            type="text"
                            name="cnpj"
                            value="{{ old('cnpj', $church->cnpj) }}"
                            maxlength="18"
                            data-mask="cnpj"
                            inputmode="numeric"
                            placeholder="00.000.000/0000-00"
                            required
                            @error('cnpj') aria-invalid="true" aria-describedby="church-cnpj-error" @enderror
                        >
                        @error('cnpj')<span class="church-field-error" id="church-cnpj-error">{{ $message }}</span>@enderror
                    </label>

                    <label>
                        Descrição / Nome Fantasia
                        <input
                            id="church-descricao"
                            type="text"
                            name="descricao"
                            value="{{ old('descricao', $church->descricao) }}"
                            maxlength="255"
                            placeholder="Nome da igreja"
                            required
                            @error('descricao') aria-invalid="true" aria-describedby="church-descricao-error" @enderror
                        >
                        @error('descricao')<span class="church-field-error" id="church-descricao-error">{{ $message }}</span>@enderror
                    </label>

                    <label>
                        Estado da Igreja
                        <select name="estado" required @error('estado') aria-invalid="true" aria-describedby="church-estado-error" @enderror>
                            <option value="">Selecione</option>
                            @foreach ($states as $stateCode => $stateLabel)
                                <option value="{{ $stateCode }}" @selected(old('estado', $church->estado) === $stateCode)>
                                    {{ $stateLabel }} ({{ $stateCode }})
                                </option>
                            @endforeach
                        </select>
                        @error('estado')<span class="church-field-error" id="church-estado-error">{{ $message }}</span>@enderror
                    </label>

                    <label>
                        Cidade da Igreja
                        <select
                            id="church-cidade"
                            name="cidade"
                            required
                            disabled
                            data-selected-city="{{ old('cidade', $church->cidade) }}"
                            @error('cidade') aria-invalid="true" aria-describedby="church-cidade-error" @enderror
                        >
                            <option value="">Selecione um estado primeiro</option>
                        </select>
                        @error('cidade')<span class="church-field-error" id="church-cidade-error">{{ $message }}</span>@enderror
                    </label>

                    <label>
                        Setor
                        <input
                            type="text"
                            name="setor"
                            value="{{ old('setor', $church->setor) }}"
                            maxlength="255"
                            placeholder="Setor da igreja"
                            @error('setor') aria-invalid="true" aria-describedby="church-setor-error" @enderror
                        >
                        @error('setor')<span class="church-field-error" id="church-setor-error">{{ $message }}</span>@enderror
                    </label>
                </div>

                <div class="field-note">
                    O código não pode ser alterado nesta etapa. A administração vinculada é obrigatória para a importação.
                </div>

                <div class="inline-actions">
                    <button class="btn primary" type="submit">Salvar alterações</button>
                    <a class="btn" href="{{ route('migration.churches.index') }}">Cancelar</a>
                </div>
            </form>
        </div>
    </section>

@endsection
