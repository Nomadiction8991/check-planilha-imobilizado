# Mapa de QA — check-planilha-imobilizado

Mantido pelo hunter (cron `hunter-check-planilha`). Cada execução atualiza
a coluna `Caçado em` e registra achados como cards no kanban.
Legenda testes: ✅ tem teste dedicado · ⚠️ parcial · ❌ sem teste.

| Módulo | Arquivos | Testes | Caçado em | Achados |
|---|---|---|---|---|
| `app/Console/Commands` | 5 | ⚠️ (2/5) | 2026-10-10 | `t_6b675e1e`: cleanup diário remove análise aguardando >7 dias; `t_42fa119e`: CLI retorna sucesso após falha |
| `app/Contracts` | 23 | ❌ | 2026-10-10 | `t_7dbfa073`: fluxo `recentImports` sem escopo retorna importações de outras administrações (P2) |
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

## Ligações entre módulos

- Fluxo importação: `SpreadsheetImportController::create` → `LegacySpreadsheetImportService::recentImports(null, 5)` → `currentAdministrationScopeIds`; sessão não-admin sem IDs retorna `null`, a query não aplica escopo e `resources/views/spreadsheets/import.blade.php` exibe metadados de importações de todos os tenants (`t_7dbfa073`). Reproduzido com usuário legado ativo em SQLite em memória.
- Auth: `LegacyAuthController::login` → `LegacyAuthSessionService::attempt` grava escopo vazio para conta legada sem administração; `spreadsheets.import` permanece concedida pelo default. Reproduzido junto ao controller da tela.
- Dependências: Trivy reportou Guzzle 7.13.1 (CVE-2026-69246 HIGH; correção 7.15.2) e Laravel 13.18.1 (CVE-2026-102279 LOW; correção 13.30.0); triagem `t_40f917b7`.
- Varredura 2026-10-10: todas as regras ast-grep sem matches; Gitleaks sem achados em `app/routes/config/database/resources`; diagnósticos Serena sem erros. O MCP Semgrep não apareceu no catálogo desta sessão. Esta build do CodeGraph não expõe `trace`; foram usados `context`, `callers`, `callees` e `impact`.

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

