<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sem resposta do servidor</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #F7F4ED; color: #272727; font: 16px/1.55 system-ui, sans-serif; }
        main { min-height: 100vh; display: grid; place-items: center; padding: 24px 16px; }
        .painel { width: 100%; max-width: 520px; padding: 24px; background: #FFFDF9; border: 1px solid #D1CDC6; border-radius: 14px; box-shadow: 0 2px 8px rgb(39 39 39 / 5%); }
        h1 { margin: 0 0 16px; font-size: 24px; line-height: 1.2; }
        p { margin: 0 0 16px; overflow-wrap: anywhere; }
        .orientacao { color: #60636A; }
        a { display: inline-flex; align-items: center; justify-content: center; min-height: 48px; padding: 12px 20px; margin-top: 8px; border-radius: 6px; background: #176DA9; color: #fff; font-weight: 600; text-decoration: none; }
        a:hover { background: #105786; }
        a:focus-visible { outline: 3px solid #105786; outline-offset: 3px; }
        @media (max-width: 480px) { a { width: 100%; } }
    </style>
</head>
<body>
    <main>
        <section class="painel" aria-labelledby="titulo">
            <h1 id="titulo">Sem resposta do servidor</h1>
            <p>{{ $exception->getMessage() ?: 'Não consegui falar com o servidor agora.' }}</p>
            <p class="orientacao">Quando a internet voltar, tente de novo.</p>
            <a href="{{ route('migration.dashboard') }}">Voltar ao início</a>
        </section>
    </main>
</body>
</html>
