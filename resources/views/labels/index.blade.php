@extends('layouts.migration')

@section('title', 'Copiar Etiquetas | ' . config('app.name'))

@section('content')
    @php
        $selectedDependencyId = $data['selected_dependency_id'];
        $manualCodes = collect($manualCodes ?? [])->values()->all();
        $churchCode = trim((string) data_get($data, 'church.codigo', ''));
    @endphp

    <style>
        .manual-tag-input {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            gap: 10px;
            align-items: center;
            padding: 14px 16px;
            border: 1px solid rgba(15, 23, 42, 0.12);
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.86);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
        }

        .manual-tag-input__prefix {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 102px;
            padding: 10px 12px;
            border-radius: 999px;
            background: rgba(59, 130, 246, 0.12);
            color: #1d4ed8;
            font-weight: 700;
            letter-spacing: 0.02em;
            white-space: nowrap;
        }

        .manual-tag-input input {
            min-width: 0;
        }

        .manual-tag-input__add {
            white-space: nowrap;
        }

        .manual-tag-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 14px;
        }

        .manual-tag-chip {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.08);
            font-weight: 600;
        }

        .manual-tag-chip button {
            width: 26px;
            height: 26px;
            border: 0;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.12);
            color: #111827;
            cursor: pointer;
            line-height: 1;
        }

        .manual-tag-chip button:hover {
            background: rgba(239, 68, 68, 0.18);
            color: #991b1b;
        }

        .manual-tag-empty {
            color: #6b7280;
            font-size: 14px;
            margin-top: 10px;
        }
    </style>

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
            <form method="GET" action="{{ route('migration.labels.index') }}">
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
                <div
                    class="manual-tag-input"
                    data-manual-labels-root
                    data-church-id="{{ $churchId ?? '' }}"
                    data-dependency-id="{{ $selectedDependencyId ?? '' }}"
                    data-church-code="{{ $churchCode }}"
                    data-save-url="{{ route('migration.labels.manual.store') }}"
                    data-csrf-token="{{ csrf_token() }}"
                    @disabled($churchId === null)
                >
                    <span class="manual-tag-input__prefix">{{ $churchCode !== '' ? $churchCode . '/' : 'Selecione uma igreja' }}</span>
                    <input
                        id="manualLabelNumberInput"
                        type="text"
                        inputmode="numeric"
                        maxlength="6"
                        placeholder="233"
                        @disabled($churchId === null)
                    >
                    <button class="btn primary manual-tag-input__add" id="addManualLabelButton" type="button" @disabled($churchId === null)>Adicionar</button>
                </div>
            </label>

            <div id="manualLabelChipList" class="manual-tag-list" style="grid-column: 1 / -1;">
                @forelse ($manualCodes as $manualCode)
                    <span class="manual-tag-chip">
                        <span>{{ $manualCode }}</span>
                        <button type="button" aria-label="Remover {{ $manualCode }}">x</button>
                    </span>
                @empty
                    <div class="manual-tag-empty">Nenhuma etiqueta manual salva ainda.</div>
                @endforelse
            </div>
            <p class="product-edit-note" style="grid-column: 1 / -1; margin: 0;">
                Digite só o número final. O sistema completa com a igreja atual, grava automaticamente e permite remover
                cada etiqueta pelo `x`.
            </p>
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
                    <button class="btn" id="copyManualLabelsButton" type="button" @disabled($manualCodes === [])>Copiar manuais</button>
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
            const root = document.querySelector('[data-manual-labels-root]');
            const chipList = document.getElementById('manualLabelChipList');
            const addButton = document.getElementById('addManualLabelButton');
            const input = document.getElementById('manualLabelNumberInput');
            const codesButton = document.getElementById('copyCodesButton');
            const allButton = document.getElementById('copyAllLabelsButton');
            const manualButton = document.getElementById('copyManualLabelsButton');
            const codesField = document.getElementById('codigosField');

            const config = root ? {
                churchId: Number(root.dataset.churchId || 0),
                dependencyId: root.dataset.dependencyId !== '' ? Number(root.dataset.dependencyId) : null,
                churchCode: root.dataset.churchCode || '',
                saveUrl: root.dataset.saveUrl || '',
                csrfToken: root.dataset.csrfToken || '',
            } : null;

            let manualCodes = @json(array_values($manualCodes));

            const renderChips = () => {
                if (!chipList) {
                    return;
                }

                chipList.innerHTML = '';

                if (!manualCodes.length) {
                    chipList.innerHTML = '<div class="manual-tag-empty">Nenhuma etiqueta manual salva ainda.</div>';
                    if (manualButton) {
                        manualButton.disabled = true;
                    }
                    return;
                }

                if (manualButton) {
                    manualButton.disabled = false;
                }

                manualCodes.forEach((code) => {
                    const chip = document.createElement('span');
                    chip.className = 'manual-tag-chip';
                    chip.innerHTML = `<span>${code}</span>`;

                    const remove = document.createElement('button');
                    remove.type = 'button';
                    remove.setAttribute('aria-label', `Remover ${code}`);
                    remove.textContent = 'x';
                    remove.addEventListener('click', () => persistManualCode('remove', code));

                    chip.appendChild(remove);
                    chipList.appendChild(chip);
                });
            };

            const normalizeNumber = (value) => value.replace(/\D+/g, '').slice(0, 6);

            const formatCode = (number) => {
                if (!config || config.churchCode === '') {
                    return '';
                }

                return `${config.churchCode.toUpperCase().trim()}/${number.padStart(6, '0')}`;
            };

            const copyText = async (text, button, successText) => {
                try {
                    await navigator.clipboard.writeText(text);
                    button.textContent = successText;
                } catch (error) {
                    button.textContent = 'Falha ao copiar';
                }

                window.setTimeout(() => {
                    if (button === manualButton) {
                        button.textContent = 'Copiar manuais';
                        return;
                    }

                    if (button === allButton) {
                        button.textContent = 'Copiar tudo';
                        return;
                    }

                    button.textContent = 'Copiar códigos gerados';
                }, 1600);
            };

            const persistManualCode = async (action, codeOrNumber) => {
                if (!config || config.saveUrl === '' || config.churchId <= 0) {
                    return;
                }

                const payload = new FormData();
                payload.append('_token', config.csrfToken);
                payload.append('comum_id', String(config.churchId));
                if (config.dependencyId !== null) {
                    payload.append('dependencia_id', String(config.dependencyId));
                }

                const normalizedNumber = action === 'add'
                    ? normalizeNumber(String(codeOrNumber))
                    : String(codeOrNumber).split('/').pop()?.replace(/\D+/g, '').slice(0, 6) || '';

                if (normalizedNumber === '') {
                    return;
                }

                payload.append('numero', normalizedNumber);
                payload.append('action', action);

                const response = await fetch(config.saveUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: payload,
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok || data.success !== true) {
                    return;
                }

                manualCodes = Array.isArray(data.codes) ? data.codes : manualCodes;
                renderChips();
                if (input) {
                    input.value = '';
                    input.focus();
                }
            };

            if (input) {
                input.addEventListener('input', () => {
                    const normalized = normalizeNumber(input.value);
                    if (input.value !== normalized) {
                        input.value = normalized;
                    }
                });

                input.addEventListener('keydown', async (event) => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        const normalized = normalizeNumber(input.value);
                        if (normalized !== '') {
                            await persistManualCode('add', normalized);
                        }
                    }
                });
            }

            if (addButton) {
                addButton.addEventListener('click', async () => {
                    const normalized = input ? normalizeNumber(input.value) : '';
                    if (normalized === '') {
                        if (input) {
                            input.focus();
                        }
                        return;
                    }

                    await persistManualCode('add', normalized);
                });
            }

            if (manualButton) {
                manualButton.addEventListener('click', async () => {
                    const manualLabels = manualCodes.join(', ');
                    if (manualLabels === '') {
                        if (input) {
                            input.focus();
                        }
                        return;
                    }

                    await copyText(manualLabels, manualButton, 'Manuais copiados');
                });
            }

            if (codesButton && codesField) {
                codesButton.addEventListener('click', async () => {
                    codesField.select();
                    await copyText(codesField.value, codesButton, 'Códigos copiados');
                });
            }

            if (allButton && codesField) {
                allButton.addEventListener('click', async () => {
                    const parts = [];
                    const codes = codesField.value.trim();
                    if (codes !== '') {
                        parts.push(codes);
                    }
                    if (manualCodes.length) {
                        parts.push(manualCodes.join(', '));
                    }

                    if (!parts.length) {
                        if (input) {
                            input.focus();
                        }
                        return;
                    }

                    await copyText(parts.join('\n\n'), allButton, 'Tudo copiado');
                });
            }

            renderChips();
        })();
    </script>
@endsection
