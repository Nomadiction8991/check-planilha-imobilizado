@extends('layouts.migration')

@section('title', 'Configurações | ' . config('app.name'))

@section('content')
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
        <div class="config-grid">
            <div class="table-shell config-card config-card--mail">
                <form method="POST" action="{{ route('migration.configuracoes.update') }}" class="form-shell">
                    @csrf
                    <input type="hidden" name="config_section" value="mail">

                    <div class="section-head section-head--stacked">
                        <div>
                            <h2>E-mail do sistema</h2>
                            <p>
                                Configure o SMTP usado para recuperar senha e enviar mensagens automáticas. Salve só
                                este bloco quando alterar credenciais de e-mail.
                            </p>
                        </div>
                    </div>

                    <div class="field-grid">
                        <label>
                            Host SMTP
                            <input
                                type="text"
                                name="mail_host"
                                value="{{ old('mail_host', $mailConfiguration->host) }}"
                                maxlength="255"
                                placeholder="smtp.gmail.com"
                                required
                            >
                            @error('mail_host') <span class="cadastro-field-error" role="alert">{{ $message }}</span> @enderror
                        </label>

                        <label>
                            Porta SMTP
                            <input
                                type="number"
                                name="mail_port"
                                value="{{ old('mail_port', (string) $mailConfiguration->port) }}"
                                min="1"
                                max="65535"
                                required
                            >
                            @error('mail_port') <span class="cadastro-field-error" role="alert">{{ $message }}</span> @enderror
                        </label>

                        <label>
                            Conexão
                            <select name="mail_scheme" required>
                                <option value="tls" @selected(old('mail_scheme', $mailConfiguration->scheme ?: 'tls') === 'tls')>TLS</option>
                                <option value="ssl" @selected(old('mail_scheme', $mailConfiguration->scheme ?: 'tls') === 'ssl')>SSL</option>
                                <option value="null" @selected(old('mail_scheme', $mailConfiguration->scheme ?: 'tls') === 'null')>Sem criptografia</option>
                            </select>
                            @error('mail_scheme') <span class="cadastro-field-error" role="alert">{{ $message }}</span> @enderror
                        </label>

                        <label>
                            E-mail Google
                            <input
                                type="email"
                                name="mail_username"
                                value="{{ old('mail_username', $mailConfiguration->username) }}"
                                maxlength="255"
                                placeholder="seuemail@gmail.com"
                                required
                            >
                            @error('mail_username') <span class="cadastro-field-error" role="alert">{{ $message }}</span> @enderror
                        </label>

                        <label>
                            Senha do aplicativo
                            <input
                                type="password"
                                name="mail_password"
                                value=""
                                maxlength="255"
                                placeholder="Digite a senha do aplicativo do Google"
                            >
                            @error('mail_password') <span class="cadastro-field-error" role="alert">{{ $message }}</span> @enderror
                        </label>

                        <label>
                            E-mail do remetente
                            <input
                                type="email"
                                name="mail_from_address"
                                value="{{ old('mail_from_address', $mailConfiguration->fromAddress ?: $mailConfiguration->username) }}"
                                maxlength="255"
                                placeholder="seuemail@gmail.com"
                                required
                            >
                            @error('mail_from_address') <span class="cadastro-field-error" role="alert">{{ $message }}</span> @enderror
                        </label>

                        <label>
                            Nome do remetente
                            <input
                                type="text"
                                name="mail_from_name"
                                value="{{ old('mail_from_name', $mailConfiguration->fromName) }}"
                                maxlength="255"
                                placeholder="Check Planilha"
                                required
                            >
                            @error('mail_from_name') <span class="cadastro-field-error" role="alert">{{ $message }}</span> @enderror
                        </label>
                    </div>

                    <p class="field-note">
                        O sistema usa essas credenciais para enviar a senha nova no fluxo de "esqueci minha senha". Se
                        você mudar a senha do Google, atualize este bloco também.
                    </p>

                    <div class="inline-actions inline-actions--end">
                        <button class="btn primary" type="submit">Salvar e-mail</button>
                    </div>
                </form>
            </div>

            @php
                $menuItemsByKey = [];

                foreach ($menuItems as $item) {
                    $menuItemsByKey[$item['key']] = $item;
                }

                $orderedMenuKeys = old('menu_order', $menuOrder->items);
                $orderedMenuItems = [];

                foreach ($orderedMenuKeys as $key) {
                    if (isset($menuItemsByKey[$key])) {
                        $orderedMenuItems[] = $menuItemsByKey[$key];
                    }
                }

                foreach ($menuItemsByKey as $key => $item) {
                    if (!in_array($key, array_column($orderedMenuItems, 'key'), true)) {
                        $orderedMenuItems[] = $item;
                    }
                }
            @endphp

            <div class="table-shell config-card config-card--menu">
                <form method="POST" action="{{ route('migration.configuracoes.update') }}" class="form-shell">
                    @csrf
                    <input type="hidden" name="config_section" value="menu">

                    <div class="menu-order-shell">
                        <div class="menu-order-head">
                            <div>
                                <h2>Ordem dos menus</h2>
                                <p>
                                    Arraste e solte os itens para definir a ordem global do menu. Salve só este bloco
                                    quando reorganizar a navegação.
                                </p>
                            </div>
                        </div>

                        <div class="menu-order-list" data-menu-order-list>
                            @foreach ($orderedMenuItems as $item)
                                <article
                                    class="menu-order-item"
                                    data-menu-order-item
                                    data-menu-key="{{ $item['key'] }}"
                                    draggable="true"
                                >
                                    <input type="hidden" name="menu_order[]" value="{{ $item['key'] }}">
                                    <span class="menu-order-handle" aria-hidden="true">drag_indicator</span>
                                    <div class="menu-order-copy">
                                        <strong>{{ $item['label'] }}</strong>
                                        <small>{{ $item['subtitle'] }}</small>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>

                    <div class="inline-actions inline-actions--end">
                        <button class="btn primary" type="submit">Salvar menu</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <style>
        .cadastro-field-error { display: block; color: var(--ui-danger); margin-top: 4px; }
        .config-card { font-size: 16px; line-height: 1.5; }
        .config-card .btn.primary { background: var(--product-primary); border-color: var(--product-primary); color: var(--product-primary-text); }
        .config-card .btn.primary:hover { background: var(--product-primary-hover); box-shadow: none; }
        .config-card :focus-visible { outline: 3px solid var(--ui-focus); outline-offset: 2px; }
        .config-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            align-items: start;
        }

        .config-card {
            position: relative;
            overflow: hidden;
        }

        .config-card::before {
            content: '';
            position: absolute;
            inset: 0 0 auto;
            height: 4px;
            background: var(--accent);
        }

        html[data-theme='dark'] .config-card {
            background: var(--surface-strong);
        }

        html[data-theme='dark'] .config-card::before {
            opacity: 0.95;
        }

        html[data-theme='dark'] .config-badge--mail {
            background: var(--accent-soft);
            border-color: var(--accent);
            color: var(--accent);
        }

        html[data-theme='dark'] .config-card--menu::before {
            background: var(--accent);
        }


        .section-head--stacked {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }

        .section-head--stacked h2 {
            margin: 0;
            font-size: 20px;
        }

        .section-head--stacked p {
            margin: 6px 0 0;
            color: var(--muted);
            max-width: 60ch;
        }


        .inline-actions--end {
            justify-content: flex-end;
        }


        .menu-order-shell {
            display: grid;
            gap: 16px;
            padding: 0;
            border: 0;
            border-radius: 0;
            background: transparent;
        }

        .menu-order-head {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 16px;
        }

        .menu-order-head h2 {
            margin: 0;
            font-size: 20px;
        }

        .menu-order-head p {
            margin: 6px 0 0;
            color: var(--muted);
        }


        .menu-order-list {
            display: grid;
            gap: 10px;
        }

        .menu-order-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            background: var(--surface);
            box-shadow: none;
            cursor: grab;
            user-select: none;
        }

        .menu-order-item.is-dragging {
            opacity: 0.55;
        }

        .menu-order-item.is-over {
            border-color: var(--accent);
            outline: 2px solid var(--ui-focus);
        }

        .menu-order-handle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 999px;
            background: var(--surface-soft);
            font-family: 'Material Symbols Outlined';
            font-size: 20px;
            color: var(--muted);
            flex: 0 0 auto;
        }

        .menu-order-copy {
            display: grid;
            gap: 2px;
            min-width: 0;
        }

        .menu-order-copy strong {
            font-size: 15px;
        }

        .menu-order-copy small {
            color: var(--muted);
        }

        @media (min-width: 1024px) {
            .config-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 720px) {
            .config-grid {
                grid-template-columns: 1fr;
            }

            .menu-order-head {
                flex-direction: column;
            }

            .menu-order-item {
                align-items: start;
            }
        }
    </style>

    <script>
        (() => {
            const list = document.querySelector('[data-menu-order-list]');

            if (!list) {
                return;
            }

            const getItems = () => Array.from(list.querySelectorAll('[data-menu-order-item]'));

            const clearOverState = () => {
                getItems().forEach((item) => item.classList.remove('is-over'));
            };

            const getDragAfterElement = (container, y) => {
                const items = [...container.querySelectorAll('[data-menu-order-item]:not(.is-dragging)')];

                return items.reduce(
                    (closest, child) => {
                        const box = child.getBoundingClientRect();
                        const offset = y - box.top - box.height / 2;

                        if (offset < 0 && offset > closest.offset) {
                            return { offset, element: child };
                        }

                        return closest;
                    },
                    { offset: Number.NEGATIVE_INFINITY, element: null }
                ).element;
            };

            let draggingItem = null;

            list.addEventListener('dragstart', (event) => {
                const item = event.target.closest('[data-menu-order-item]');

                if (!item) {
                    return;
                }

                draggingItem = item;
                item.classList.add('is-dragging');
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', item.dataset.menuKey || '');
            });

            list.addEventListener('dragend', () => {
                if (draggingItem) {
                    draggingItem.classList.remove('is-dragging');
                }

                draggingItem = null;
                clearOverState();
            });

            list.addEventListener('dragover', (event) => {
                event.preventDefault();

                if (!draggingItem) {
                    return;
                }

                const afterElement = getDragAfterElement(list, event.clientY);

                if (afterElement === null) {
                    list.appendChild(draggingItem);
                } else if (afterElement !== draggingItem) {
                    list.insertBefore(draggingItem, afterElement);
                }
            });

            list.addEventListener('dragenter', (event) => {
                const item = event.target.closest('[data-menu-order-item]');

                if (!item || item === draggingItem) {
                    return;
                }

                clearOverState();
                item.classList.add('is-over');
            });

            list.addEventListener('dragleave', (event) => {
                const item = event.target.closest('[data-menu-order-item]');

                if (item) {
                    item.classList.remove('is-over');
                }
            });
        })();
    </script>
@endsection
