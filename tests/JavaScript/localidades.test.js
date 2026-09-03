import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import vm from 'node:vm';

const localidadesPath = fileURLToPath(new URL('../../public/assets/forms/localidades.js', import.meta.url));
const localidadesSource = readFileSync(localidadesPath, 'utf8');

function deferred() {
    let resolve;
    let reject;
    const promise = new Promise((resolvePromise, rejectPromise) => {
        resolve = resolvePromise;
        reject = rejectPromise;
    });

    return { promise, resolve, reject };
}

function createSelect({ value = '', selectedCity = '' } = {}) {
    const listeners = new Map();
    const select = {
        value,
        disabled: false,
        dataset: { selectedCity },
        options: [],
        addEventListener(type, listener) {
            const current = listeners.get(type) ?? [];
            current.push(listener);
            listeners.set(type, current);
        },
        dispatchEvent(event) {
            for (const listener of listeners.get(event.type) ?? []) {
                listener.call(select, event);
            }
        },
        appendChild(option) {
            select.options.push(option);
            if (option.selected) {
                select.value = option.value;
            }
        },
        get selectedIndex() {
            return select.options.findIndex((option) => option.value === select.value);
        },
    };

    let markup = '';
    Object.defineProperty(select, 'innerHTML', {
        get() {
            return markup;
        },
        set(value) {
            markup = String(value);
            select.options = [];
            select.value = '';

            if (markup.includes('<option')) {
                select.appendChild({
                    value: '',
                    textContent: markup.includes('Carregando')
                        ? 'Carregando cidades...'
                        : markup.includes('não foi possível')
                            ? 'Não foi possível carregar as cidades'
                            : 'Selecione',
                    selected: true,
                });
            }
        },
    });

    return select;
}

async function settle() {
    await new Promise((resolve) => setImmediate(resolve));
    await Promise.resolve();
}

async function waitForRequest(requests, expectedCount) {
    for (let attempt = 0; attempt < 20 && requests.length < expectedCount; attempt += 1) {
        await settle();
    }

    assert.equal(requests.length, expectedCount);
}

async function runRaceScenario({ supportsAbort }) {
    const stateSelect = createSelect({ value: 'MT' });
    const citySelect = createSelect({ selectedCity: 'Cuiabá' });
    const requests = [];
    const controllers = [];
    const domReadyListeners = [];
    const errors = [];

    class FakeAbortController {
        constructor() {
            this.signal = { aborted: false, controller: this };
            this.abortCount = 0;
            controllers.push(this);
        }

        abort() {
            this.signal.aborted = true;
            this.abortCount += 1;
        }
    }

    const fetch = (url, options) => {
        const response = deferred();
        requests.push({ url, options, response });

        return response.promise.then((payload) => ({
            ok: true,
            json: async () => payload,
        }));
    };

    const document = {
        readyState: 'loading',
        addEventListener(type, listener) {
            if (type === 'DOMContentLoaded') {
                domReadyListeners.push(listener);
            }
        },
        querySelector(selector) {
            return {
                '#endereco_estado': stateSelect,
                '#endereco_cidade': citySelect,
            }[selector] ?? null;
        },
        createElement() {
            return { value: '', textContent: '', selected: false };
        },
    };

    const window = {};
    const sandbox = {
        AbortController: supportsAbort ? FakeAbortController : undefined,
        console: { error: (...args) => errors.push(args) },
        document,
        fetch,
        window,
    };
    vm.createContext(sandbox);
    vm.runInContext(localidadesSource, sandbox, { filename: localidadesPath });

    assert.equal(domReadyListeners.length, 1);
    assert.equal(typeof window.LegacyLocalidades.refresh, 'function');

    const refreshPromise = window.LegacyLocalidades.refresh();
    assert.equal(requests[0].url, '/api/localidades/estados');
    assert.equal(requests[0].options.signal?.aborted ?? false, false);

    requests[0].response.resolve({
        success: true,
        data: [
            { sigla: 'MT', nome: 'Mato Grosso' },
            { sigla: 'SP', nome: 'São Paulo' },
        ],
    });
    await waitForRequest(requests, 2);

    assert.equal(requests[1].url, '/api/localidades/estados/MT/municipios');
    requests[1].response.resolve({ success: true, data: [{ nome: 'Cuiabá' }] });
    await refreshPromise;
    assert.equal(citySelect.value, 'Cuiabá');

    stateSelect.value = 'SP';
    stateSelect.dispatchEvent({ type: 'change' });
    await waitForRequest(requests, 3);
    const staleRequest = requests[2];

    stateSelect.value = 'MT';
    stateSelect.dispatchEvent({ type: 'change' });
    await waitForRequest(requests, 4);
    const currentRequest = requests[3];

    assert.equal(citySelect.disabled, true);
    assert.equal(citySelect.options[0].textContent, 'Carregando cidades...');
    if (supportsAbort) {
        assert.equal(staleRequest.options.signal.controller.abortCount, 1);
        assert.equal(staleRequest.options.signal.aborted, true);
    } else {
        assert.equal('signal' in staleRequest.options, false);
    }

    currentRequest.response.resolve({ success: true, data: [{ nome: 'Rondonópolis' }] });
    await settle();
    await settle();
    assert.equal(citySelect.disabled, false);
    assert.equal(citySelect.value, '');
    assert.deepEqual(citySelect.options.map((option) => option.value), ['', 'Rondonópolis']);

    staleRequest.response.resolve({ success: true, data: [{ nome: 'São Paulo' }] });
    await settle();
    await settle();

    assert.equal(citySelect.disabled, false);
    assert.equal(citySelect.value, '');
    assert.deepEqual(citySelect.options.map((option) => option.value), ['', 'Rondonópolis']);
    assert.equal(errors.length, 0);
    assert.equal(controllers.length > 0, supportsAbort);
}

await runRaceScenario({ supportsAbort: true });
await runRaceScenario({ supportsAbort: false });
console.log('Respostas obsoletas de localidades são ignoradas com e sem AbortController.');
