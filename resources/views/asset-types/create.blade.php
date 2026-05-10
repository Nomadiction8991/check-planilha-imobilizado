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
            <form method="POST" action="{{ route('migration.asset-types.store') }}" class="form-shell">
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
@endsection
