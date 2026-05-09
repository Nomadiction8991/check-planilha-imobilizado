@extends('layouts.migration')

@section('title', 'Novo Produto | ' . config('app.name'))

@section('content')
    <section class="hero">
        <span class="eyebrow">Cadastro de produtos</span>
        <h1>Novo produto.</h1>
        <p class="hero-copy">
            Preencha os dados principais e, quando houver nota fiscal, complete as informações de emissão. O
            formulário preserva o multiplicador, a condição 14.1 e as regras do cadastro.
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
            <form method="POST" action="{{ route('migration.products.store') }}" class="form-shell">
                @csrf

                <div class="field-grid">
                    <label>
                        Igreja
                        <select name="comum_id" id="comum_id" required>
                            <option value="">Selecione</option>
                            @foreach ($churches as $church)
                                <option value="{{ $church->id }}" @selected((int) old('comum_id', $selectedChurchId) === (int) $church->id)>
                                    {{ $church->codigo }} - {{ $church->descricao }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        Código
                        <input type="text" name="codigo" value="{{ old('codigo') }}" maxlength="50" placeholder="Código externo">
                    </label>

                    <label>
                        Multiplicador
                        <input type="number" name="multiplicador" min="1" value="{{ old('multiplicador', 1) }}" required>
                    </label>

                    <label>
                        Tipo de bem
                        <select name="id_tipo_ben" id="id_tipo_ben" required>
                            <option value="">Selecione</option>
                            @foreach ($assetTypes as $assetType)
                                <option value="{{ $assetType->id }}" @selected((int) old('id_tipo_ben') === (int) $assetType->id)>
                                    {{ $assetType->codigo }} - {{ $assetType->descricao }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        Bem
                        <select name="tipo_ben" id="tipo_ben" required></select>
                    </label>

                    <label>
                        Dependência
                        <select name="id_dependencia" id="id_dependencia" required></select>
                    </label>
                </div>

                <label>
                    Complemento
                    <textarea name="complemento" rows="3" required>{{ old('complemento') }}</textarea>
                </label>

                <div class="field-grid">
                    <label>
                        Altura (m)
                        <input type="number" name="altura_m" value="{{ old('altura_m') }}" min="0" step="0.001" placeholder="Ex.: 1.200">
                    </label>

                    <label>
                        Largura (m)
                        <input type="number" name="largura_m" value="{{ old('largura_m') }}" min="0" step="0.001" placeholder="Ex.: 0.800">
                    </label>

                    <label>
                        Comprimento (m)
                        <input type="number" name="comprimento_m" value="{{ old('comprimento_m') }}" min="0" step="0.001" placeholder="Ex.: 2.500">
                    </label>
                </div>

                <div class="field-grid">
                    <label>
                        <span>Imprimir 14.1</span>
                        <select name="imprimir_14_1">
                            <option value="0" @selected(!old('imprimir_14_1'))>Não</option>
                            <option value="1" @selected((int) old('imprimir_14_1') === 1)>Sim</option>
                        </select>
                    </label>

                    <label>
                        Condição 14.1
                        <select name="condicao_14_1" id="condicao_14_1" required>
                            <option value="1" @selected(old('condicao_14_1', '2') === '1')>Mais de cinco anos com documento</option>
                            <option value="2" @selected(old('condicao_14_1', '2') === '2')>Mais de cinco anos sem documento</option>
                            <option value="3" @selected(old('condicao_14_1', '2') === '3')>Até cinco anos com documento</option>
                        </select>
                    </label>
                </div>

                <div id="invoice_fields" class="field-grid">
                    <label>
                        Número da nota
                        <input type="number" name="nota_numero" value="{{ old('nota_numero') }}" min="1">
                    </label>

                    <label>
                        Data da nota
                        <input type="date" name="nota_data" value="{{ old('nota_data') }}">
                    </label>

                    <label>
                        Valor da nota
                        <input type="text" name="nota_valor" value="{{ old('nota_valor') }}" placeholder="0,00">
                    </label>

                    <label>
                        Fornecedor
                        <input type="text" name="nota_fornecedor" value="{{ old('nota_fornecedor') }}" maxlength="255">
                    </label>
                </div>

                <div class="inline-actions">
                    <button class="btn primary" type="submit">Salvar produto</button>
                    <a class="btn" href="{{ route('migration.products.index', array_filter(['comum_id' => old('comum_id', $selectedChurchId)])) }}">Cancelar</a>
                </div>
            </form>
        </div>
    </section>

    <script>
        (() => {
            const typeMap = @json($assetTypeOptionMap);
            const dependencyMap = @json($dependencyOptionMap);
            const churchSelect = document.getElementById('comum_id');
            const typeSelect = document.getElementById('id_tipo_ben');
            const itemSelect = document.getElementById('tipo_ben');
            const dependencySelect = document.getElementById('id_dependencia');
            const conditionSelect = document.getElementById('condicao_14_1');
            const invoiceFields = document.getElementById('invoice_fields');
            const oldItem = @json(old('tipo_ben'));
            const oldDependency = @json((int) old('id_dependencia'));

            function refillItems() {
                const typeId = typeSelect.value;
                const selected = typeMap[typeId] ? typeMap[typeId].options : [];
                itemSelect.innerHTML = '<option value=\"\">Selecione</option>';

                selected.forEach((option) => {
                    const element = document.createElement('option');
                    element.value = option;
                    element.textContent = option;
                    if (oldItem === option) {
                        element.selected = true;
                    }
                    itemSelect.appendChild(element);
                });
            }

            function refillDependencies() {
                const churchId = churchSelect.value;
                const selected = dependencyMap[churchId] || [];
                dependencySelect.innerHTML = '<option value=\"\">Selecione</option>';

                selected.forEach((option) => {
                    const element = document.createElement('option');
                    element.value = option.id;
                    element.textContent = option.descricao;
                    if (Number(oldDependency) === Number(option.id)) {
                        element.selected = true;
                    }
                    dependencySelect.appendChild(element);
                });
            }

            function toggleInvoice() {
                const condition = conditionSelect.value;
                invoiceFields.style.display = (condition === '1' || condition === '3') ? '' : 'none';
            }

            churchSelect.addEventListener('change', refillDependencies);
            typeSelect.addEventListener('change', refillItems);
            conditionSelect.addEventListener('change', toggleInvoice);

            refillItems();
            refillDependencies();
            toggleInvoice();
        })();
    </script>
@endsection
