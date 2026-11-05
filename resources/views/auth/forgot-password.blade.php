<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar senha | {{ config('app.name') }}</title>
    @include('partials.theme-init')
    @include('partials.pwa')
    @include('partials.design-tokens')
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 16px; font-family: Inter, system-ui, sans-serif; font-size: 16px; line-height: 1.5; color: var(--ui-text); background: var(--ui-bg); }
        .card { width: min(560px, 100%); padding: 24px; display: grid; gap: 16px; border: 1px solid var(--ui-border); border-radius: var(--radius-lg); background: var(--ui-surface); box-shadow: var(--shadow-card); }
        .card-head, .inline-actions { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
        h1, p { margin: 0; }
        h1 { font-size: 24px; line-height: 1.25; }
        .eyebrow { color: var(--product-primary); font-weight: 700; }
        .copy { color: var(--ui-text-muted); }
        form, .field-grid { display: grid; gap: 16px; }
        label { display: grid; gap: 6px; font-weight: 600; }
        input { width: 100%; min-height: 44px; padding: 10px 12px; border: 1px solid var(--ui-border); border-radius: var(--radius-sm); background: var(--ui-surface); color: var(--ui-text); font: inherit; }
        input:focus-visible, button:focus-visible, .link:focus-visible { outline: 3px solid var(--ui-focus); outline-offset: 2px; }
        button { min-height: 44px; padding: 10px 16px; border: 1px solid var(--product-primary); border-radius: var(--radius-sm); background: var(--product-primary); color: var(--product-primary-text); font: inherit; font-weight: 700; cursor: pointer; }
        button:hover { background: var(--product-primary-hover); }
        button:active { transform: translateY(1px); }
        button:disabled { opacity: .6; cursor: wait; }
        .link { min-height: 44px; display: inline-flex; align-items: center; color: var(--product-primary); font-weight: 700; }
        .flash { padding: 14px 16px; border: 1px solid var(--ui-danger); border-radius: var(--radius-sm); color: var(--ui-danger); }
        .flash.success { border-color: var(--ui-success); color: var(--ui-success); }
        .field-error { color: var(--ui-danger); font-weight: 400; }
        ul { margin: 8px 0 0; padding-left: 18px; }
        @media (min-width: 640px) { .card { padding: 32px; } }
    </style>
    @include('partials.theme-toggle-assets')
    <style>
        html[data-theme='dark'] {
            --ui-bg: var(--bg);
            --ui-surface: var(--surface);
            --ui-text: var(--ink);
            --ui-text-muted: var(--muted);
            --ui-border: var(--line);
        }
        html[data-theme='dark'] .card { background: var(--surface); color: var(--ink); }
    </style>
</head>
<body>
    <main class="card">
        <div class="card-head">
            <span class="eyebrow">Recuperação de acesso</span>
            @include('partials.theme-toggle')
        </div>
        <h1>Esqueci minha senha</h1>
        <p class="copy">Informe CPF, telefone e e-mail exatamente como estão no cadastro. Se os dados baterem, o sistema gera uma senha nova e envia para o e-mail informado.</p>

        @if (session('status'))
            <div class="flash {{ session('status_type', 'success') === 'error' ? '' : 'success' }}">
                <strong>{{ session('status') }}</strong>
            </div>
        @endif

        @if ($errors->any())
            <div class="flash">
                <strong>Revise os dados informados.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('migration.password.store') }}">
            @csrf

            <div class="field-grid">
                <label>
                    CPF
                    <input type="text" name="cpf" value="{{ old('cpf') }}" required maxlength="14" data-mask="cpf" inputmode="numeric" autocomplete="off">
                    @error('cpf')<span class="field-error">{{ $message }}</span>@enderror
                </label>

                <label>
                    Telefone
                    <input type="tel" name="telefone" value="{{ old('telefone') }}" required maxlength="15" data-mask="phone" inputmode="numeric" autocomplete="off">
                    @error('telefone')<span class="field-error">{{ $message }}</span>@enderror
                </label>

                <label>
                    E-mail cadastrado
                    <input type="email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email">
                    @error('email')<span class="field-error">{{ $message }}</span>@enderror
                </label>
            </div>

            <div class="inline-actions">
                <button type="submit">Gerar nova senha</button>
                <a class="link" href="{{ route('migration.login') }}">Voltar ao login</a>
            </div>
        </form>
    </main>
    @include('partials.request-loading')
</body>
</html>
