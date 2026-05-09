@extends('layouts.migration')

@section('title', 'Configurações | ' . config('app.name'))

@section('content')
    <section class="hero">
        <span class="eyebrow">Acesso administrativo</span>
        <h1>Configurações.</h1>
        <p class="hero-copy">
            Use esta tela para configurar o e-mail de envio do sistema. No momento, só a conta do Google SMTP é
            necessária para que a recuperação de senha funcione.
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
            <form method="POST" action="{{ route('migration.configuracoes.update') }}" class="form-shell">
                @csrf

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
                    </label>

                    <label>
                        Conexão
                        <select name="mail_scheme" required>
                            <option value="tls" @selected(old('mail_scheme', $mailConfiguration->scheme ?: 'tls') === 'tls')>TLS</option>
                            <option value="ssl" @selected(old('mail_scheme', $mailConfiguration->scheme ?: 'tls') === 'ssl')>SSL</option>
                            <option value="null" @selected(old('mail_scheme', $mailConfiguration->scheme ?: 'tls') === 'null')>Sem criptografia</option>
                        </select>
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
                    </label>
                </div>

                <p class="field-note">
                    O sistema usa essas credenciais para enviar a senha nova no fluxo de "esqueci minha senha". Se você
                    mudar a senha do Google, atualize este campo também.
                </p>

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

                <div class="menu-order-shell">
                    <div class="menu-order-head">
                        <div>
                            <h2>Ordem dos menus</h2>
                            <p>
                                Arraste e solte os itens para definir a ordem global do menu. Essa personalização vale
                                para todos os usuários.
                            </p>
                        </div>
                        <span class="menu-order-badge">Global</span>
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

                <div class="inline-actions">
                    <button class="btn primary" type="submit">Salvar configurações</button>
                </div>
            </form>
        </div>
    </section>

    <style>
        .menu-order-shell {
            display: grid;
            gap: 16px;
            margin-top: 8px;
            padding: 18px;
            border: 1px solid var(--line);
            border-radius: 24px;
            background: linear-gradient(180deg, rgba(255,255,255,0.65), rgba(255,255,255,0.92));
        }

        .menu-order-head {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 16px;
        }

        .menu-order-head h2 {
            margin: 0;
            font-size: 18px;
        }

        .menu-order-head p {
            margin: 6px 0 0;
            color: var(--muted);
        }

        .menu-order-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.08);
            color: var(--text);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
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
            border-radius: 18px;
            background: var(--surface);
            box-shadow: var(--shadow-soft);
            cursor: grab;
            user-select: none;
        }

        .menu-order-item.is-dragging {
            opacity: 0.55;
        }

        .menu-order-item.is-over {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.16);
        }

        .menu-order-handle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.06);
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

        @media (max-width: 720px) {
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
