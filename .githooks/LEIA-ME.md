# Hooks do Git

- `pre-commit`: bloqueia o commit quando há arquivo de ambiente (`.env`) com valores, chave de aplicação com valor, chave privada ou token de acesso. Modelos (`.env.example`) com valor vazio e o texto `null` passam.

Ative uma vez em cada clone do projeto:

    git config core.hooksPath .githooks

Se o verificador bloquear algo que não é segredo, ajuste o padrão em `.githooks/pre-commit` e o caso correspondente em `tests/Unit/Deploy/PreCommitTest.php`.
