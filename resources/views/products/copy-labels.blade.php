@extends('layouts.migration')

@section('title', 'Copiar Etiquetas | ' . config('app.name'))

@section('content')
    @php
        $selectedDependencyId = $data['selected_dependency_id'];
    @endphp

    <section class="hero">
        <span class="eyebrow">Etiquetas de produto</span>
        <h1>Copiar códigos para etiquetas.</h1>
        <p class="hero-copy">
            Use esta tela para gerar a lista de códigos sem alterar a marcação já feita na listagem de produtos.
        </p>
    </section>

    @if (session('status'))
        <div class="flash-stack">
            <div class="flash {{ session('status_type', 'success') === 'error' ? 'error' : 'success' }}">
                <strong>{{ session('status') }}</strong>
            </div>
        </div>
    @endif

    <section class="section">
        <div class="filters">
            <form method="GET" action="{{ route('migration.compat.products.copy-labels') }}">
                <label>
                    Igreja
                    <select name="comum_id" onchange="this.form.submit()">
                        <option value="">Selecione uma igreja</option>
                        @foreach ($churches as $church)
                            <option value="{{ $church->id }}" @selected((int) $churchId === (int) $church->id)>
                                {{ $church->codigo }} - {{ $church->descricao }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label>
                    Dependência
                    <select name="dependencia" @disabled($churchId === null)>
                        <option value="">Todas as dependências</option>
                        @foreach ($data['dependencies'] as $dependency)
                            <option value="{{ $dependency['id'] }}" @selected((string) $selectedDependencyId === (string) $dependency['id'])>
                                {{ $dependency['descricao'] }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <div class="actions">
                    <button class="btn primary" type="submit">Filtrar</button>
                    <a class="btn" href="{{ route('migration.products.index', ['comum_id' => $churchId]) }}">Voltar aos produtos</a>
                </div>
            </form>
        </div>
    </section>

    <section class="section">
        <div class="filters">
            <label style="grid-column: 1 / -1;">
                Etiquetas manuais
                <textarea
                    id="manualLabelsField"
                    rows="6"
                    placeholder="Uma etiqueta por linha"
                ></textarea>
            </label>

            <p class="product-edit-note" style="grid-column: 1 / -1; margin: 0;">
                Use uma linha por etiqueta para acrescentar textos manuais e copiar tudo junto com os códigos gerados.
            </p>

            <div class="actions">
                <button class="btn" id="copyManualLabelsButton" type="button">Copiar etiquetas manuais</button>
            </div>
        </div>
    </section>

    <section class="section">
        @if ($data['products'] !== [])
            <div class="filters">
                <label style="grid-column: 1 / -1;">
                    Códigos
                    <textarea id="codigosField" rows="8" readonly onclick="this.select()">{{ $data['codes'] }}</textarea>
                </label>

                <div class="actions">
                    <button class="btn primary" id="copyCodesButton" type="button">Copiar códigos gerados</button>
                    <button class="btn" id="copyAllLabelsButton" type="button">Copiar tudo</button>
                </div>
            </div>
        @else
            <div class="empty-state">
                <strong>Nenhum produto disponível para etiquetas.</strong>
                <p>
                    @if ($churchId === null)
                        Selecione uma igreja acima para carregar as etiquetas.
                    @elseif ($selectedDependencyId)
                        Não há produtos marcados para etiqueta na dependência selecionada.
                    @else
                        Marque produtos com o ícone de etiqueta para gerar a lista de códigos.
                    @endif
                </p>
            </div>
        @endif
    </section>

    <script>
        (() => {
            const codesButton = document.getElementById('copyCodesButton');
            const allButton = document.getElementById('copyAllLabelsButton');
            const manualButton = document.getElementById('copyManualLabelsButton');
            const codesField = document.getElementById('codigosField');
            const manualField = document.getElementById('manualLabelsField');

            const normalizeLines = (value) => value
                .split(/\r?\n/)
                .map((line) => line.trim())
                .filter((line) => line !== '');

            const copyText = async (text, button, successText) => {
                try {
                    await navigator.clipboard.writeText(text);
                    button.textContent = successText;
                } catch (error) {
                    button.textContent = 'Falha ao copiar';
                }

                window.setTimeout(() => {
                    if (button === manualButton) {
                        button.textContent = 'Copiar etiquetas manuais';
                        return;
                    }

                    if (button === allButton) {
                        button.textContent = 'Copiar tudo';
                        return;
                    }

                    button.textContent = 'Copiar códigos gerados';
                }, 1600);
            };

            if (manualButton && manualField) {
                manualButton.addEventListener('click', async () => {
                    const manualLabels = normalizeLines(manualField.value).join('\n');

                    if (manualLabels === '') {
                        manualField.focus();
                        return;
                    }

                    await copyText(manualLabels, manualButton, 'Etiquetas manuais copiadas');
                });
            }

            if (codesButton && codesField) {
                codesButton.addEventListener('click', async () => {
                    codesField.select();

                    await copyText(codesField.value, codesButton, 'Códigos copiados');
                });
            }

            if (allButton && codesField && manualField) {
                allButton.addEventListener('click', async () => {
                    const codes = codesField.value.trim();
                    const manualLabels = normalizeLines(manualField.value).join('\n');

                    const parts = [];

                    if (codes !== '') {
                        parts.push(codes);
                    }

                    if (manualLabels !== '') {
                        parts.push(manualLabels);
                    }

                    if (parts.length === 0) {
                        manualField.focus();
                        return;
                    }

                    await copyText(parts.join('\n\n'), allButton, 'Tudo copiado');
                });
            }
        })();
    </script>
@endsection
