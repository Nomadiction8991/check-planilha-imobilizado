# Mapa de QA — check-planilha-imobilizado

Mantido pelo hunter (cron `hunter-check-planilha`). Cada execução atualiza
a coluna `Caçado em` e registra achados como cards no kanban.
Legenda testes: ✅ tem teste dedicado · ⚠️ parcial · ❌ sem teste.

| Módulo | Arquivos | Testes | Caçado em | Achados |
|---|---|---|---|---|
| `app/Console/Commands` | 5 | ⚠️ (2/5) | 2026-10-10 | `t_6b675e1e`: cleanup diário remove análise aguardando >7 dias; `t_42fa119e`: CLI retorna sucesso após falha |
| `app/Contracts` | 23 | ❌ | — | — |
| `app/Core` | 3 | ⚠️ (2/3) | — | — |
| `app/DTO` | 22 | ⚠️ (9/22) | — | — |
| `app/Features/Factories` | 1 | ❌ | — | — |
| `app/Http/Controllers` | 17 | ⚠️ (15/17) | — | — |
| `app/Http/Middleware` | 6 | ⚠️ (4/6) | — | — |
| `app/Http/Requests` | 17 | ❌ | 2026-10-10 | varredura limpa — authorize coberto por legacy.auth; administracao_id conferido no service; email/CPF únicos no service |
| `app/Mail` | 1 | ❌ | — | — |
| `app/Models` | 1 | ✅ | — | — |
| `app/Models/Legacy` | 6 | ⚠️ (1/6) | — | — |
| `app/Providers` | 1 | ❌ | — | — |
| `app/Repositories` | 6 | ⚠️ (1/6) | — | — |
| `app/Services` | 30 | ⚠️ (16/30) | — | — |
| `app/Support` | 7 | ⚠️ (3/7) | — | — |
| `app/ValueObjects` | 1 | ❌ | — | — |

Total: 147 arquivos PHP em `app/`.

## Ligações entre módulos (preencher caçando)

- Fluxo importação: `SpreadsheetImportController` → ? → ? (mapear)
- Auth: `LegacyAuthController` → ? (mapear)

## Índice de símbolos (`tags`)

Arquivo `docs/qa-mapa/tags` (ctags, gerado — não commitar): definições de
classes/métodos de `app/`, `routes/`, `config/`, `database/`.
Regenerar após mudanças grandes: `ctags -R --languages=PHP --exclude=vendor
--exclude=node_modules --exclude=.git --fields=+niaz app routes config database`
(com `export PATH=/opt/data/tools/php-env/bin:$PATH`).
Uso na caça: `grep -n "NomeClasse\|nomeMetodo" docs/qa-mapa/tags` para achar
definições; `grep -rn "->nomeMetodo(" app routes` para achar chamadores.

## Grafo de código (CodeGraph)

Índice SQLite em `.codegraph/` (gerado, não commitar) com símbolos, chamadas e
imports. CLI: `export PATH=/opt/data/tools/codegraph/node_modules/.bin:/opt/hermes/tools/node-26.7.0-linux-x64/bin:$PATH`.
Comandos úteis na caça:
- `codegraph context "NomeClasse"` — como X funciona (definição + chamadores + chamados)
- `codegraph callers "metodo"` / `codegraph callees "metodo"` — um salto no grafo
- `codegraph trace --from A --to B` — caminho de chamadas de A até B
- `codegraph impact "simbolo"` — o que quebra se mudar isso
- `codegraph sync` — reindexar após mudanças grandes (`codegraph status` mostra saúde)

