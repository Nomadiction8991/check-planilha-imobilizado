<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar | {{ config('app.name') }}</title>
    @include('partials.theme-init')
    @include('partials.pwa')
    <style>
        :root {
            --bg: #F7F4ED;
            --surface: #FFFDF9;
            --ink: #272727;
            --muted: #60636A;
            --line: #D1CDC6;
            --accent: #176DA9;
            --accent-hover: #105786;
            --radius: 14px;
            --radius-sm: 12px;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            font-family: var(--font-sans, system-ui, -apple-system, sans-serif);
            color: var(--ink);
            background: var(--bg);
        }

        .card {
            width: min(480px, 100%);
            border: 1px solid var(--line);
            background: var(--surface);
            box-shadow: 0 8px 24px rgba(39, 39, 39, 0.08);
            border-radius: var(--radius);
            padding: 32px;
            display: grid;
            gap: 16px;
        }

        .card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        h1, p { margin: 0; }

        .eyebrow {
            color: var(--accent);
            font-size: 12px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            display: inline-flex;
            width: fit-content;
            padding: 6px 10px;
            border-radius: 999px;
            background: #DDECF7;
        }

        h1 { font-size: 26px; font-weight: 600; }

        .copy {
            color: var(--muted);
            line-height: 1.5;
        }

        form {
            display: grid;
            gap: 12px;
            margin-top: 6px;
        }

        .form-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .forgot-link {
            color: var(--accent);
            text-decoration: none;
            font-size: 16px;
            font-weight: 700;
            min-height: 48px;
            display: inline-flex;
            align-items: center;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        label {
            display: grid;
            gap: 6px;
            font-size: 16px;
            font-weight: 500;
            color: var(--ink);
        }

        input {
            width: 100%;
            min-height: 48px;
            padding: 11px 12px;
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            background: #fff;
            color: var(--ink);
            font: inherit;
            transition: border-color 0.16s ease, box-shadow 0.16s ease;
        }

        input:focus {
            outline: none;
            border-color: var(--accent-hover);
            box-shadow: 0 0 0 3px rgba(16, 87, 134, 0.15);
        }

        button:not(.theme-toggle) {
            min-height: 48px;
            padding: 12px 16px;
            border: 1px solid var(--accent);
            border-radius: var(--radius-sm);
            background: var(--accent);
            color: #fffdf8;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.16s ease, background-color 0.16s ease, box-shadow 0.16s ease;
        }

        button:not(.theme-toggle):hover {
            background: var(--accent-hover);
            border-color: var(--accent-hover);
        }

        button:not(.theme-toggle):focus-visible, .forgot-link:focus-visible {
            outline: 3px solid var(--accent-hover);
            outline-offset: 3px;
        }

        .flash {
            margin-top: 18px;
            padding: 14px 16px;
            border: 1px solid #B33A3A;
            border-radius: var(--radius-sm);
            color: #B33A3A;
            background: #FDF2F2;
        }

        .flash.success {
            color: #267454;
            border-color: #267454;
            background: #F0FDF4;
        }

        .product-footer { color: var(--muted); text-align: center; }

        ul {
            margin: 8px 0 0;
            padding-left: 18px;
        }

        @media (max-width: 480px) {
            body {
                padding: 16px;
            }

            .card {
                padding: 24px;
            }
        }
    </style>
    @include('partials.theme-toggle-assets')
    <style>
        html[data-theme='light'] {
            --bg: #F7F4ED;
            --surface: #FFFDF9;
            --ink: #272727;
            --muted: #60636A;
            --line: #D1CDC6;
            --accent: #176DA9;
        }
        html[data-theme='light'] body { background: var(--bg); }
        html[data-theme='light'] .card { background: var(--surface); box-shadow: 0 8px 24px rgba(39, 39, 39, 0.08); }
        html[data-theme='light'] .eyebrow { color: var(--accent); background: #DDECF7; }
        html[data-theme='light'] button[type='submit'] { background: var(--accent); border-color: var(--accent); box-shadow: none; }
        html[data-theme='light'] button[type='submit']:hover { background: var(--accent-hover); border-color: var(--accent-hover); }
        html[data-theme='light'] .flash { color: #B33A3A; border-color: #B33A3A; background: #FDF2F2; box-shadow: none; }
        html[data-theme='light'] .flash.success { color: #267454; border-color: #267454; background: #F0FDF4; }
        html[data-theme='dark'] .flash { color: #ffdada; border-color: #e78888; background: #542b2b; }
        html[data-theme='dark'] .flash.success { color: #d3f5df; border-color: #79c99b; background: #224437; }
    </style>
</head>
<body>
    <main class="card">
        <div class="card-head">
            <span class="eyebrow">Check Planilha</span>
            @include('partials.theme-toggle')
        </div>
        <h1>Entrar</h1>
        <p class="copy">Acesso restrito</p>
        <p class="copy">Use suas credenciais para acessar o sistema.</p>

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

        <form method="POST" action="{{ route('migration.login.store') }}">
            @csrf
            <label>
                E-mail
                <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
            </label>

            <label>
                Senha
                <input type="password" name="senha" required autocomplete="current-password">
            </label>

            <div class="form-footer">
                <button type="submit">Entrar</button>
                <a class="forgot-link" href="{{ route('migration.password.request') }}">Esqueci minha senha</a>
            </div>
        </form>
        <p class="product-footer">Um produto Anvy Softwares</p>
    </main>
    @include('partials.request-loading')
</body>
</html>
