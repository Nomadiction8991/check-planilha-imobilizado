<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar senha | {{ config('app.name') }}</title>
    @include('partials.theme-init')
    @include('partials.pwa')
    <style>
        :root {
            --bg: #f5f0e8;
            --surface: rgba(255, 252, 247, 0.94);
            --ink: #181511;
            --muted: #6f6253;
            --line: rgba(24, 21, 17, 0.12);
            --accent: #1f6f5f;
            --warn: #8b3d19;
            --shadow: 0 16px 38px rgba(38, 28, 12, 0.08);
            --shadow-strong: 0 24px 60px rgba(38, 28, 12, 0.12);
            --radius: 22px;
            --radius-sm: 12px;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            font-family: Georgia, "Times New Roman", serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, rgba(31, 111, 95, 0.14), transparent 30%),
                radial-gradient(circle at right center, rgba(139, 61, 25, 0.14), transparent 24%),
                linear-gradient(180deg, #f8f3ed 0%, var(--bg) 100%);
        }

        .card {
            width: min(560px, 100%);
            border: 1px solid var(--line);
            background: var(--surface);
            box-shadow: var(--shadow-strong);
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
            background: rgba(31, 111, 95, 0.12);
        }

        .copy {
            color: var(--muted);
            line-height: 1.5;
        }

        form {
            display: grid;
            gap: 12px;
            margin-top: 6px;
        }

        .field-grid {
            display: grid;
            gap: 12px;
        }

        label {
            display: grid;
            gap: 6px;
            font-size: 13px;
            color: var(--muted);
        }

        input {
            width: 100%;
            min-height: 44px;
            padding: 11px 12px;
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            background: #fff;
            color: var(--ink);
            transition: border-color 0.16s ease, box-shadow 0.16s ease;
        }

        input:focus {
            outline: none;
            border-color: rgba(31, 111, 95, 0.55);
            box-shadow: 0 0 0 3px rgba(31, 111, 95, 0.12);
        }

        button {
            min-height: 44px;
            padding: 12px 16px;
            border: 1px solid var(--ink);
            border-radius: var(--radius-sm);
            background: var(--ink);
            color: #fffdf8;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.16s ease, background-color 0.16s ease, box-shadow 0.16s ease;
        }

        button:hover {
            transform: translateY(-1px);
            background: #1f1a14;
            box-shadow: 0 8px 20px rgba(38, 28, 12, 0.12);
        }

        .inline-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 6px;
        }

        .link {
            color: var(--accent);
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
        }

        .link:hover {
            text-decoration: underline;
        }

        .flash {
            margin-top: 18px;
            padding: 14px 16px;
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            background: rgba(139, 61, 25, 0.08);
        }

        .flash.success {
            background: rgba(31, 111, 95, 0.08);
        }

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
                </label>

                <label>
                    Telefone
                    <input type="text" name="telefone" value="{{ old('telefone') }}" required maxlength="15" data-mask="phone" inputmode="numeric" autocomplete="off">
                </label>

                <label>
                    E-mail cadastrado
                    <input type="email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email">
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
