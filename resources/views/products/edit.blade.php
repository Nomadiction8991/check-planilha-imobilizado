@extends('layouts.migration')

@section('title', 'Editar Produto | ' . config('app.name'))

@section('content')
    <style>
        .product-edit-layout {
            display: grid;
            gap: 18px;
        }

        .product-edit-split {
            display: grid;
            gap: 18px;
            grid-template-columns: minmax(0, 0.92fr) minmax(0, 1.08fr);
            align-items: start;
        }

        .product-edit-section {
            display: grid;
            gap: 14px;
            padding: 18px;
            border: 1px solid var(--line);
            border-radius: 22px;
            box-shadow: var(--shadow-soft);
        }

        .product-edit-section__head {
            display: grid;
            gap: 6px;
        }

        .product-edit-section__tag {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .product-edit-section__title {
            font-size: 20px;
            line-height: 1.15;
            letter-spacing: -0.01em;
        }

        .product-edit-section__copy {
            color: var(--muted);
            line-height: 1.55;
        }

        .product-edit-section--current {
            cursor: not-allowed;
            background:
                linear-gradient(180deg, color-mix(in srgb, var(--surface-strong) 90%, var(--warn-soft) 10%), var(--surface));
        }

        .product-edit-section--current .product-edit-section__tag {
            color: var(--muted);
            background: color-mix(in srgb, var(--surface-soft) 86%, var(--ink) 14%);
        }

        .product-edit-section--current .field-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .product-edit-section--current :is(input, select, textarea):disabled {
            cursor: not-allowed;
            color: var(--muted);
            background: color-mix(in srgb, var(--surface-strong) 78%, var(--ink) 22%);
            border-color: color-mix(in srgb, var(--line) 84%, var(--warn) 16%);
            box-shadow: none;
            opacity: 0.92;
        }

        .product-edit-section--current :is(input, select, textarea):disabled:hover {
            transform: none;
            box-shadow: none;
        }

        .product-edit-section--new {
            background:
                linear-gradient(180deg, color-mix(in srgb, var(--surface-strong) 86%, var(--accent-soft) 14%), var(--surface));
            border-color: color-mix(in srgb, var(--line) 76%, var(--accent) 24%);
        }

        .product-edit-section--new .product-edit-section__tag {
            color: var(--accent);
            background: var(--accent-soft);
        }

        .product-edit-group {
            display: grid;
            gap: 12px;
            padding: 14px;
            border: 1px solid color-mix(in srgb, var(--line) 82%, var(--accent) 18%);
            border-radius: 18px;
            background: var(--surface-soft);
        }

        .product-edit-group__head {
            display: grid;
            gap: 4px;
        }

        .product-edit-group__head strong {
            font-size: 16px;
        }

        .product-edit-group__head small {
            color: var(--muted);
            line-height: 1.45;
        }

        .product-edit-checks {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .product-edit-check {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 54px;
            padding: 14px 16px;
            border: 1px solid var(--line);
            border-radius: 16px;
            background: color-mix(in srgb, var(--surface-strong) 84%, var(--surface) 16%);
            transition: background-color 0.16s ease, border-color 0.16s ease, transform 0.16s ease, box-shadow 0.16s ease;
        }

        .product-edit-check:hover {
            border-color: color-mix(in srgb, var(--accent-soft) 42%, var(--line) 58%);
            background: color-mix(in srgb, var(--surface-strong) 82%, var(--accent-soft) 18%);
            transform: translateY(-1px);
            box-shadow: 0 10px 22px rgba(38, 28, 12, 0.06);
        }

        .product-edit-check input {
            margin: 0;
        }

        .product-edit-check span {
            font-size: 15px;
        }

        .product-edit-note {
            font-size: 12px;
            color: var(--muted);
        }

        @media (max-width: 980px) {
            .product-edit-split {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 860px) {
            .product-edit-section--current .field-grid,
            .product-edit-checks {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <section class="hero">
        <span class="eyebrow">Cadastro de produtos</span>
        <h1>Editar produto.</h1>
        <p class="hero-copy">
            Os dados atuais ficam bloqueados como referência. Use os campos ao lado para registrar a nova edição e,
            quando necessário, ajustar condição 14.1 e nota fiscal.
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
            <form method="POST" action="{{ route('migration.products.update', ['product' => $product->id_produto]) }}" class="form-shell product-edit-layout">
                @csrf
                @method('PUT')
                <input type="hidden" name="return_url" value="{{ request()->query('return_url', url()->previous()) }}">

                @php
                    $currentChurchCode = trim((string) data_get($product, 'comum.codigo', ''));
                    $currentChurchDescription = trim((string) data_get($product, 'comum.descricao', ''));
                    $currentChurch = $currentChurchCode !== '' && $currentChurchDescription !== ''
                        ? $currentChurchCode . ' - ' . $currentChurchDescription
                        : 'Nenhuma';
                    $currentCode = $product->codigo !== '' ? $product->codigo : 'Sem código';
                    $currentTypeCode = trim((string) data_get($product, 'tipoBem.codigo', ''));
                    $currentTypeDescription = trim((string) data_get($product, 'tipoBem.descricao', ''));
                    $currentType = $currentTypeCode !== '' && $currentTypeDescription !== ''
                        ? $currentTypeCode . ' - ' . $currentTypeDescription
                        : ($currentTypeDescription !== '' ? $currentTypeDescription : 'Nenhum');
                    $currentDependency = trim((string) data_get($product, 'dependencia.descricao', ''));
                    $currentDependency = $currentDependency !== '' ? $currentDependency : 'Nenhuma';
                    $selectedType = old('novo_tipo_bem_id', $product->editado_tipo_bem_id ?: $product->tipo_bem_id);
                    $selectedDependency = old('nova_dependencia_id', $product->editado_dependencia_id ?: $product->dependencia_id);
                    $selectedCondition = old('condicao_14_1', $product->condicao_14_1 ?: '2');
                @endphp

                <div class="product-edit-split">
                    <fieldset class="product-edit-section product-edit-section--current" disabled>
                        <div class="product-edit-section__head">
                            <span class="product-edit-section__tag">Somente leitura</span>
                            <strong class="product-edit-section__title">Valores atuais</strong>
                            <p class="product-edit-section__copy">
                                Esses dados ficam bloqueados para servir de referência durante a edição.
                            </p>
                        </div>

                        <div class="field-grid">
                            <label>
                                Igreja
                                <input type="text" value="{{ $currentChurch }}" disabled>
                            </label>

                            <label>
                                Código
                                <input type="text" value="{{ $currentCode }}" disabled>
                            </label>

                            <label>
                                Tipo de bem atual
                                <input type="text" value="{{ $currentType }}" disabled>
                            </label>

                            <label>
                                Dependência atual
                                <input type="text" value="{{ $currentDependency }}" disabled>
                            </label>
                        </div>
                    </fieldset>

                    <div class="product-edit-section product-edit-section--new">
                        <div class="product-edit-section__head">
                            <span class="product-edit-section__tag">Edição</span>
                            <strong class="product-edit-section__title">Novos valores</strong>
                            <p class="product-edit-section__copy">
                                Preencha apenas o que será alterado. O restante permanece separado no bloco de referência.
                            </p>
                        </div>

                        <div class="product-edit-group">
                            <div class="product-edit-group__head">
                                <strong>Cadastro principal</strong>
                                <small>Tipo, bem, dependência e complemento que vão substituir a versão atual.</small>
                            </div>

                            <div class="field-grid">
                                <label>
                                    Novo tipo de bem
                                    <select name="novo_tipo_bem_id" id="novo_tipo_bem_id" required>
                                        <option value="">Selecione</option>
                                        @foreach ($assetTypes as $assetType)
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
                                            <option value="{{ $dependency->id }}" @selected((int) $selectedDependency === (int) $dependency->id)>
                                                {{ $dependency->descricao }}
                                            </option>
                                        @endforeach
                                    </select>
                                </label>
                            </div>

                            <label>
                                Novo complemento
                                <textarea name="novo_complemento" rows="3" required>{{ old('novo_complemento', $product->editado_complemento ?: $product->complemento) }}</textarea>
                            </label>
                        </div>

                        <div class="product-edit-group">
                            <div class="product-edit-group__head">
                                <strong>Conferência</strong>
                                <small>Marcação visual e observação rápida do cadastro.</small>
                            </div>

                            <div class="product-edit-checks">
                                <label class="product-edit-check">
                                    <input type="hidden" name="verificado" value="0">
                                    <input type="checkbox" name="verificado" value="1" @checked((bool) old('verificado', (int) $product->checado === 1))>
                                    <span>Verificado</span>
                                </label>

                                <label class="product-edit-check">
                                    <input type="hidden" name="imprimir_etiqueta" value="0">
                                    <input type="checkbox" name="imprimir_etiqueta" value="1" @checked((bool) old('imprimir_etiqueta', (int) $product->imprimir_etiqueta === 1))>
                                    <span>Etiqueta</span>
                                </label>
                            </div>

                            <label>
                                Observação
                                <textarea name="observacao" rows="3" maxlength="255" placeholder="Observação rápida">{{ old('observacao', $product->observacao) }}</textarea>
                            </label>
                        </div>

                        <div class="product-edit-group">
                            <div class="product-edit-group__head">
                                <strong>Documento e impressão</strong>
                                <small>Use só quando a regra de 14.1 ou a nota fiscal precisarem acompanhar a edição.</small>
                            </div>

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
                        </div>

                        <div class="inline-actions">
                            <button class="btn primary" type="submit">Salvar alterações</button>
                            <a class="btn" href="{{ route('migration.products.index', ['comum_id' => $product->comum_id]) }}">Cancelar</a>
                        </div>
                    </div>
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
