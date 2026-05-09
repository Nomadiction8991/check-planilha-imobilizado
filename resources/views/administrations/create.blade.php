@extends('layouts.migration')

@section('title', 'Nova Administração | ' . config('app.name'))

@section('content')
    <section class="hero">
        <span class="eyebrow">Cadastro administrativo</span>
        <h1>Nova administração.</h1>
        <p class="hero-copy">
            Cadastre a administração que depois será selecionada no envio da planilha e vinculada às igrejas.
            O CNPJ será usado nos relatórios da igreja vinculada.
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
            <form method="POST" action="{{ route('migration.administrations.store') }}" class="form-shell">
                @csrf

                <div class="field-grid">
                    <label>
                        Descrição
                        <input
                            type="text"
                            name="descricao"
                            value="{{ old('descricao') }}"
                            maxlength="255"
                            placeholder="Ex.: Administração Central"
                            required
                        >
                    </label>

                    <label>
                        CNPJ
                        <input
                            type="text"
                            name="cnpj"
                            value="{{ old('cnpj') }}"
                            maxlength="18"
                            data-mask="cnpj"
                            inputmode="numeric"
                            placeholder="00.000.000/0000-00"
                            required
                        >
                    </label>

                    <label>
                        Estado
                        <select name="estado" id="administration-estado" required>
                            <option value="">Selecione</option>
                            @foreach ((array) config('brazil.states', []) as $stateCode => $stateLabel)
                                <option value="{{ $stateCode }}" @selected(old('estado') === $stateCode)>
                                    {{ $stateLabel }} ({{ $stateCode }})
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        Cidade
                        <select name="cidade" id="administration-cidade" required disabled>
                            <option value="">Selecione um estado primeiro</option>
                        </select>
                    </label>
                </div>

                <p class="field-note">A descrição será usada no seletor da importação. Estado e cidade ajudam a identificar a administração vinculada.</p>

                <div class="inline-actions">
                    <button class="btn primary" type="submit">Salvar administração</button>
                    <a class="btn" href="{{ route('migration.administrations.index') }}">Cancelar</a>
                </div>
            </form>
        </div>
    </section>

    <script>
        (() => {
            const stateSelect = document.getElementById('administration-estado');
            const citySelect = document.getElementById('administration-cidade');
            const selectedCity = @json(old('cidade'));
            const citiesEndpointTemplate = "{{ route('migration.api.localidades.cities', ['state' => '__STATE__']) }}";

            async function loadCities(state, cityValue = '') {
                citySelect.disabled = true;
                citySelect.innerHTML = '<option value="">Carregando cidades...</option>';

                try {
                    const response = await fetch(citiesEndpointTemplate.replace('__STATE__', encodeURIComponent(state)), {
                        headers: { Accept: 'application/json' },
                    });
                    const payload = await response.json();

                    if (!response.ok || payload.success !== true || !Array.isArray(payload.data)) {
                        throw new Error(payload.message || 'Não foi possível carregar as cidades.');
                    }

                    const cities = payload.data;

                    citySelect.innerHTML = '<option value="">Selecione</option>';

                    cities.forEach((city) => {
                        const option = document.createElement('option');
                        option.value = city;
                        option.textContent = city;
                        if (cityValue && cityValue === city) {
                            option.selected = true;
                        }
                        citySelect.appendChild(option);
                    });

                    citySelect.disabled = false;
                } catch (error) {
                    citySelect.innerHTML = '<option value="">Não foi possível carregar as cidades</option>';
                }
            }

            stateSelect?.addEventListener('change', () => {
                const state = stateSelect.value;
                if (!state) {
                    citySelect.innerHTML = '<option value="">Selecione um estado primeiro</option>';
                    citySelect.disabled = true;
                    return;
                }

                loadCities(state);
            });

            if (stateSelect?.value) {
                loadCities(stateSelect.value, selectedCity);
            }
        })();
    </script>
@endsection
