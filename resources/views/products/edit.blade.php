@extends('layouts.migration')

@section('title', 'Editar Produto | ' . config('app.name'))

@section('content')
    <section class="hero">
        <span class="eyebrow">Cadastro de produtos</span>
        <h1>Editar produto.</h1>
        <p class="hero-copy">
            Atualize os dados do produto sem alterar os valores originais e continue ajustando condição 14.1 e nota
            fiscal quando necessário.
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
            <form method="POST" action="{{ route('migration.products.update', ['product' => $product->id_produto]) }}" class="form-shell">
                @csrf
                @method('PUT')

                <div class="field-grid">
                    <label>
                        Igreja
                        <input type="text" value="{{ data_get($product, 'comum.codigo') }} - {{ data_get($product, 'comum.descricao') }}" readonly>
                    </label>

                    <label>
                        Código
                        <input type="text" value="{{ $product->codigo ?: 'sem código' }}" readonly>
                    </label>

                    <label>
                        Tipo de bem atual
                        <input type="text" value="{{ data_get($product, 'tipoBem.codigo') }} - {{ data_get($product, 'tipoBem.descricao') }}" readonly>
                    </label>

                    <label>
                        Dependência atual
                        <input type="text" value="{{ data_get($product, 'dependencia.descricao', 'n/a') }}" readonly>
                    </label>

                    <label>
                        Novo tipo de bem
                        <select name="novo_tipo_bem_id" id="novo_tipo_bem_id" required>
                            <option value="">Selecione</option>
                            @foreach ($assetTypes as $assetType)
                                @php
                                    $selectedType = old('novo_tipo_bem_id', $product->editado_tipo_bem_id ?: $product->tipo_bem_id);
                                @endphp
                                <option value="{{ $assetType->id }}" @selected((int) $selectedType === (int) $assetType->id)>
                                    {{ $assetType->codigo }} - {{ $assetType->descricao }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        Novo bem
                        <select name="novo_bem" id="novo_bem" required></select>
                    </label>

                    <label>
                        Nova dependência
                        <select name="nova_dependencia_id" required>
                            <option value="">Selecione</option>
                            @foreach ($dependencies as $dependency)
                                @php
                                    $selectedDependency = old('nova_dependencia_id', $product->editado_dependencia_id ?: $product->dependencia_id);
                                @endphp
                                <option value="{{ $dependency->id }}" @selected((int) $selectedDependency === (int) $dependency->id)>
                                    {{ $dependency->descricao }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <div class="field-grid">
                    <label class="check-inline">
                        <input type="hidden" name="verificado" value="0">
                        <input type="checkbox" name="verificado" value="1" @checked((bool) old('verificado', (int) $product->checado === 1))>
                        <span>Verificado</span>
                    </label>

                    <label class="check-inline">
                        <input type="hidden" name="imprimir_etiqueta" value="0">
                        <input type="checkbox" name="imprimir_etiqueta" value="1" @checked((bool) old('imprimir_etiqueta', (int) $product->imprimir_etiqueta === 1))>
                        <span>Etiqueta</span>
                    </label>
                </div>

                <label>
                    Observação
                    <textarea name="observacao" rows="3" maxlength="255" placeholder="Observação rápida">{{ old('observacao', $product->observacao) }}</textarea>
                </label>

                <label>
                    Novo complemento
                    <textarea name="novo_complemento" rows="3" required>{{ old('novo_complemento', $product->editado_complemento ?: $product->complemento) }}</textarea>
                </label>

                <div class="field-grid">
                    <label>
                        <span>Imprimir 14.1</span>
                        <select name="imprimir_14_1">
                            <option value="0" @selected((int) old('imprimir_14_1', $product->imprimir_14_1) === 0)>Não</option>
                            <option value="1" @selected((int) old('imprimir_14_1', $product->imprimir_14_1) === 1)>Sim</option>
                        </select>
                    </label>

                    <label>
                        Condição 14.1
                        <select name="condicao_14_1" id="condicao_14_1" required>
                            @php($selectedCondition = old('condicao_14_1', $product->condicao_14_1 ?: '2'))
                            <option value="1" @selected($selectedCondition === '1')>Mais de cinco anos com documento</option>
                            <option value="2" @selected($selectedCondition === '2')>Mais de cinco anos sem documento</option>
                            <option value="3" @selected($selectedCondition === '3')>Até cinco anos com documento</option>
                        </select>
                    </label>
                </div>

                <div id="invoice_fields" class="field-grid">
                    <label>
                        Número da nota
                        <input type="number" name="nota_numero" value="{{ old('nota_numero', $product->nota_numero) }}" min="1">
                    </label>

                    <label>
                        Data da nota
                        <input type="date" name="nota_data" value="{{ old('nota_data', $product->nota_data) }}">
                    </label>

                    <label>
                        Valor da nota
                        <input type="text" name="nota_valor" value="{{ old('nota_valor', $product->nota_valor) }}" placeholder="0,00">
                    </label>

                    <label>
                        Fornecedor
                        <input type="text" name="nota_fornecedor" value="{{ old('nota_fornecedor', $product->nota_fornecedor) }}" maxlength="255">
                    </label>
                </div>

                <div class="inline-actions">
                    <button class="btn primary" type="submit">Salvar alterações</button>
                    <a class="btn" href="{{ route('migration.products.index', ['comum_id' => $product->comum_id]) }}">Cancelar</a>
                </div>
            </form>
        </div>
    </section>

    <script>
        (() => {
            const typeMap = @json($assetTypeOptionMap);
            const typeSelect = document.getElementById('novo_tipo_bem_id');
            const itemSelect = document.getElementById('novo_bem');
            const conditionSelect = document.getElementById('condicao_14_1');
            const invoiceFields = document.getElementById('invoice_fields');
            const oldItem = @json(old('novo_bem', $product->editado_bem ?: $product->bem));

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

            function toggleInvoice() {
                const condition = conditionSelect.value;
                invoiceFields.style.display = (condition === '1' || condition === '3') ? '' : 'none';
            }

            typeSelect.addEventListener('change', refillItems);
            conditionSelect.addEventListener('change', toggleInvoice);

            refillItems();
            toggleInvoice();
        })();
    </script>
@endsection
