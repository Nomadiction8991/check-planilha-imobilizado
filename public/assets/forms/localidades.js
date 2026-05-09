(() => {
    function onlyText(value) {
        return String(value ?? '').trim();
    }

    function normalizeCityName(item) {
        if (typeof item === 'string') {
            return onlyText(item);
        }

        return onlyText(item?.nome ?? item?.city ?? item?.label ?? '');
    }

    function normalizeStateValue(item) {
        if (typeof item === 'string') {
            return onlyText(item).toUpperCase();
        }

        return onlyText(item?.sigla ?? item?.uf ?? item?.value ?? '').toUpperCase();
    }

    function normalizeStateLabel(item) {
        if (typeof item === 'string') {
            return onlyText(item);
        }

        return onlyText(item?.nome ?? item?.label ?? item?.description ?? '');
    }

    async function fetchJson(url) {
        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
            },
        });
        const payload = await response.json();

        if (!response.ok || payload.success !== true || !Array.isArray(payload.data)) {
            throw new Error(payload.message || 'Não foi possível carregar os dados.');
        }

        return payload.data;
    }

    function resetSelect(select, message) {
        if (!select) {
            return;
        }

        select.innerHTML = '';
        const option = document.createElement('option');
        option.value = '';
        option.textContent = message;
        select.appendChild(option);
        select.disabled = true;
    }

    function fillStates(select, states, selectedValue) {
        if (!select) {
            return;
        }

        const currentValue = onlyText(selectedValue || select.value).toUpperCase();

        select.innerHTML = '<option value="">Selecione</option>';

        states.forEach((item) => {
            const value = normalizeStateValue(item);
            const label = normalizeStateLabel(item);

            if (!value || !label) {
                return;
            }

            const option = document.createElement('option');
            option.value = value;
            option.textContent = `${label} (${value})`;
            if (currentValue && currentValue === value) {
                option.selected = true;
            }
            select.appendChild(option);
        });
    }

    function fillCities(select, cities, selectedValue) {
        if (!select) {
            return;
        }

        const currentValue = onlyText(selectedValue || select.value);

        select.innerHTML = '<option value="">Selecione</option>';

        cities.forEach((item) => {
            const city = normalizeCityName(item);

            if (!city) {
                return;
            }

            const option = document.createElement('option');
            option.value = city;
            option.textContent = city;
            if (currentValue && currentValue === city) {
                option.selected = true;
            }
            select.appendChild(option);
        });
    }

    async function loadStates(select, endpoint, selectedValue) {
        if (!select || !endpoint) {
            return false;
        }

        try {
            const states = await fetchJson(endpoint);
            fillStates(select, states, selectedValue);
            select.disabled = false;
            return true;
        } catch (error) {
            console.error('Erro ao carregar estados:', error);
            return false;
        }
    }

    async function loadCities(select, endpointTemplate, state, selectedValue) {
        if (!select) {
            return false;
        }

        if (!state) {
            resetSelect(select, 'Selecione um estado primeiro');
            return false;
        }

        select.disabled = true;
        select.innerHTML = '<option value="">Carregando cidades...</option>';

        try {
            const cities = await fetchJson(endpointTemplate.replace('__STATE__', encodeURIComponent(state)));
            fillCities(select, cities, selectedValue);
            select.disabled = false;
            return true;
        } catch (error) {
            console.error('Erro ao carregar cidades:', error);
            resetSelect(select, 'Não foi possível carregar as cidades');
            return false;
        }
    }

    async function initPair(config) {
        const stateSelect = document.querySelector(config.stateSelector);
        const citySelect = document.querySelector(config.citySelector);

        if (!stateSelect || !citySelect) {
            return;
        }

        const selectedState = onlyText(config.selectedState ?? stateSelect.value).toUpperCase();
        const selectedCity = onlyText(config.selectedCity ?? citySelect.dataset.selectedCity ?? citySelect.value);

        await loadStates(stateSelect, config.statesEndpoint, selectedState);

        const state = onlyText(stateSelect.value).toUpperCase() || selectedState;
        if (state) {
            await loadCities(citySelect, config.citiesEndpointTemplate, state, selectedCity);
        } else {
            resetSelect(citySelect, 'Selecione um estado primeiro');
        }

        stateSelect.addEventListener('change', () => {
            const stateValue = onlyText(stateSelect.value).toUpperCase();
            loadCities(citySelect, config.citiesEndpointTemplate, stateValue);
        });
    }

    async function refresh() {
        await Promise.all([
            initPair({
                stateSelector: '#administration-estado',
                citySelector: '#administration-cidade',
                statesEndpoint: '/api/localidades/estados',
                citiesEndpointTemplate: '/api/localidades/estados/__STATE__/municipios',
            }),
            initPair({
                stateSelector: 'select[name="estado"]',
                citySelector: '#church-cidade',
                statesEndpoint: '/api/localidades/estados',
                citiesEndpointTemplate: '/api/localidades/estados/__STATE__/municipios',
            }),
            initPair({
                stateSelector: '#endereco_estado',
                citySelector: '#endereco_cidade',
                statesEndpoint: '/api/localidades/estados',
                citiesEndpointTemplate: '/api/localidades/estados/__STATE__/municipios',
                selectedCity: document.querySelector('#endereco_cidade')?.dataset.selectedCity || '',
            }),
        ]);
    }

    window.LegacyLocalidades = {
        refresh,
        loadStates,
        loadCities,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            refresh();
        });
    } else {
        refresh();
    }
})();
