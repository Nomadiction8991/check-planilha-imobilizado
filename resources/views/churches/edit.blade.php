@extends('layouts.migration')

@section('title', 'Editar Igreja | ' . config('app.name'))

@section('content')
    <section class="hero">
        <span class="eyebrow">Cadastro de igrejas</span>
        <h1>Editar igreja.</h1>
        <p class="hero-copy">
            Atualize o cadastro principal da igreja. O código permanece imutável, o CNPJ continua validado antes de
            gravar e a igreja precisa ficar vinculada a uma administração para funcionar na importação.
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
            <form id="church-edit-form" method="POST" action="{{ route('migration.churches.update', ['church' => $church->id]) }}" class="form-shell">
                @csrf
                @method('PUT')

                <div class="field-grid">
                    <label>
                        Administração vinculada
                        <select name="administracao_id" required>
                            <option value="">Selecione</option>
                            @foreach ($administrations as $administration)
                                <option value="{{ $administration->id }}" @selected((int) old('administracao_id', $church->administracao_id ?? 0) === (int) $administration->id)>
                                    #{{ $administration->id }} - {{ $administration->descricao }}
                                </option>
                            @endforeach
                        </select>
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
                        >
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
                        >
                    </label>

                    <label>
                        Estado da Igreja
                        <select name="estado" required>
                            <option value="">Selecione</option>
                            @foreach ($states as $stateCode => $stateLabel)
                                <option value="{{ $stateCode }}" @selected(old('estado', $church->estado) === $stateCode)>
                                    {{ $stateLabel }} ({{ $stateCode }})
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        Cidade da Igreja
                        <select
                            id="church-cidade"
                            name="cidade"
                            required
                            disabled
                            data-selected-city="{{ old('cidade', $church->cidade) }}"
                        >
                            <option value="">Selecione um estado primeiro</option>
                        </select>
                    </label>

                    <label>
                        Setor
                        <input
                            type="text"
                            name="setor"
                            value="{{ old('setor', $church->setor) }}"
                            maxlength="255"
                            placeholder="Setor da igreja"
                        >
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
