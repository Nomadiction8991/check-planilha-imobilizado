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
                <input type="hidden" name="comum_id" value="{{ $churchId }}">

                <label>
                    Dependência
                    <select name="dependencia">
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
        @if ($data['products'] !== [])
            <div class="filters">
                <label style="grid-column: 1 / -1;">
                    Códigos
                    <textarea id="codigosField" rows="8" readonly onclick="this.select()">{{ $data['codes'] }}</textarea>
                </label>

                <div class="actions">
                    <button class="btn primary" id="copyCodesButton" type="button">Copiar códigos</button>
                </div>
            </div>
        @else
            <div class="empty-state">
                <strong>Nenhum produto disponível para etiquetas.</strong>
                <p>
                    @if ($selectedDependencyId)
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
            const button = document.getElementById('copyCodesButton');
            const field = document.getElementById('codigosField');

            if (!button || !field) {
                return;
            }

            button.addEventListener('click', async () => {
                field.select();

                try {
                    await navigator.clipboard.writeText(field.value);
                    button.textContent = 'Códigos copiados';
                    window.setTimeout(() => {
                        button.textContent = 'Copiar códigos';
                    }, 1600);
                } catch (error) {
                    button.textContent = 'Falha ao copiar';
                    window.setTimeout(() => {
                        button.textContent = 'Copiar códigos';
                    }, 1600);
                }
            });
        })();
    </script>
@endsection
