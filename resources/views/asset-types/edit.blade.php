@extends('layouts.migration')

@section('title', 'Editar Tipo de Bem | ' . config('app.name'))

@section('content')
    <section class="hero">
        <span class="eyebrow">Cadastro de bens</span>
        <h1>Editar tipo de bem.</h1>
        <p class="hero-copy">
            Atualize a descrição do catálogo mestre sem alterar o código do tipo, preservando as referências já
            existentes no inventário.
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
            <form method="POST" action="{{ route('migration.asset-types.update', $assetType) }}" class="form-shell">
                @csrf
                @method('PUT')

                <div class="field-grid">
                    <label>
                        Administração
                        <select name="administracao_id" required>
                            <option value="">Selecione</option>
                            @foreach ($administrations as $administration)
                                <option value="{{ $administration->id }}" @selected((int) old('administracao_id', $selectedAdministrationId ?? $assetType->administracao_id ?? '') === (int) $administration->id)>
                                    #{{ $administration->id }} - {{ $administration->descricao }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        Código
                        <input type="text" value="{{ $assetType->codigo }}" readonly>
                    </label>

                    <label>
                        Descrição
                        <input
                            type="text"
                            name="descricao"
                            value="{{ old('descricao', $assetType->descricao) }}"
                            maxlength="255"
                            placeholder="Ex.: IMÓVEIS"
                            required
                        >
                    </label>
                </div>

                <p class="field-note">Exclusão fica disponível na listagem e é bloqueada quando já existem produtos vinculados. O tipo de bem sempre pertence a uma administração.</p>

                <div class="inline-actions">
                    <button class="btn primary" type="submit">Salvar alterações</button>
                    <a class="btn" href="{{ route('migration.asset-types.index') }}">Cancelar</a>
                </div>
            </form>
        </div>
    </section>
@endsection
