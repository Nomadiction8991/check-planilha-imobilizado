# Mapa de QA — check-planilha-imobilizado

Mantido pelo hunter (cron `hunter-check-planilha`). Cada execução atualiza
a coluna `Caçado em` e registra achados como cards no kanban.
Legenda testes: ✅ tem teste dedicado · ⚠️ parcial · ❌ sem teste.

| Módulo | Arquivos | Testes | Caçado em | Achados |
|---|---|---|---|---|
| `app/Console/Commands` | 5 | ⚠️ (2/5) | 2026-10-10 | `t_6b675e1e`: cleanup diário remove análise aguardando >7 dias; `t_42fa119e`: CLI retorna sucesso após falha durante processamento; `t_1c542e00`: `--todas` consulta PostgreSQL e mascara erro como lista vazia (P2) |
| `app/Contracts` | 23 | ❌ | 2026-10-10 | `t_7dbfa073`: fluxo `recentImports` sem escopo retorna importações de outras administrações (P2) |
| `app/Core` | 3 | ⚠️ (2/3) | 2026-10-10 | sem bug acionável direto; `QueryCache::get` não tem callsite ativo; probes de ambiente/DSN não confirmaram impacto suportado; rastreio do CLI gerou `t_1c542e00` em Console |
| `app/DTO` | 22 | ⚠️ (9/22) | 2026-10-10 | `t_8b8f1ff0`: `UserMutationData.active=false` desativa usuário administrador, mas a sessão ativa mantém acesso global (P1; reprodução confirmada) |
| `app/Features/Factories` | 1 | ❌ | — | — |
| `app/Http/Controllers` | 17 | ⚠️ (15/17) | 2026-10-10 | `t_2eb000ef`: injeção de fórmula no CSV do relatório 14.1 (P2) |
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
- Revogação de acesso: `UserMutationData.active=false` → `LegacyUserManagementService::update` persiste `ativo=0`, mas `LegacyNativeSessionBridge::normalizeAuthenticatedPayload` não verifica o estado ativo e reatribui `is_admin` pelo papel. `RequireLegacySession` só exige `usuario_id > 0`; a permissão administrativa e `LegacyProductController::index` continuam acessíveis. Prova em SQLite em memória: novo login negado, sessão antiga aprovada pelos middlewares e listagem retornou produtos de duas administrações (`t_8b8f1ff0`, P1).
- Fluxo CLI `--todas`: `.env.example` define `DB_CONNECTION=mysql` e `docker-compose.yml` oferece o serviço `db` MySQL; `ProcessarImportacoes::handle` chama `getPendingImports`, que usa `DB::connection('pgsql')` (linha 123). Se a conexão falha, o catch retorna `[]` (131–134), e o handle imprime “Nenhuma importação pendente” com exit code 0 (44–46), sem processar os IDs no MySQL. Prova isolada via `php -r`/Artisan com SQLite em memória sem tabela: erro de consulta, mensagem de lista vazia, código 0. Triage `t_1c542e00`; distinto de `t_42fa119e` (falha dentro de `processSingle`).
- Dependências: achados originais de Guzzle/Laravel registrados em `t_40f917b7` foram corrigidos; Trivy no estado deste run não reportou vulnerabilidades HIGH/CRITICAL, corrigíveis nem secrets.
- Varredura 2026-10-10 (árvore de trabalho; `composer.json`/`composer.lock` já estavam modificados): ast-grep executou as duas regras; `empty-catch` só marcou `tests/Unit/Core/ConnectionManagerTest.php:154`, `raw-blade-echo` sem matches; Gitleaks sem achados em `app/routes/config/database/resources`; Trivy sem vulnerabilidades HIGH/CRITICAL ou corrigíveis e sem secrets; diagnósticos Serena vazios nos 3 arquivos de `app/Core`. MCP Semgrep está configurado, mas a conexão falhou por falta de OAuth em sessão não interativa. CodeGraph desta build não expõe `trace`; usei `context`, `callers`, `callees` e `impact`.
- Varredura 2026-10-10 (DTO/autorização; suíte não executada): Repomix cobriu `app/DTO`; ast-grep executou ambas as regras (`empty-catch` só em teste, `raw-blade-echo` sem matches); Gitleaks sem leaks em `app/routes/config/database/resources`; Trivy sem HIGH/CRITICAL, vulnerabilidades corrigíveis ou secrets. Semgrep MCP falhou por falta de OAuth em sessão cron. Serena foi ativada e usada para localizar DTOs e referências reais em import/config; chamadas posteriores resolveram o root ativo em OdeWize, então a cadeia de revogação foi rastreada via CodeGraph. `codegraph trace` não existe nesta build; usei `context`, `node`, `callers`, `callees` e `impact`. Probes: `current()` não expõe senha SMTP, JSON boolean `false` de auditoria segue `false`, e sessão de admin desativado manteve acesso global (`t_8b8f1ff0`).

- Varredura 2026-10-10 (Controllers; suíte não executada): CodeGraph sincronizou 6 arquivos e rastreou `LegacyProductController@store` → `LegacyProductManagementService::createMany` → `LegacyReportService::downloadFormularioCsv`; `callers`, `callees` e `impact` rodaram, mas esta build não expõe `trace`. Serena confirmou referências reais e diagnósticos sem erro nos arquivos centrais (há apenas um Hint P1003 de variável não usada em `LegacyRouteCompatibilityController`). Repomix gerou pacote de 224266 bytes; ast-grep executou todas as regras (`empty-catch` marcou somente teste; `raw-blade-echo` sem matches); Gitleaks sem leaks; Trivy sem findings reportáveis (HIGH/CRITICAL, corrigíveis ou secrets). Semgrep MCP estava habilitado, mas a sessão cron não tinha token OAuth em cache. Prova isolada em SQLite `:memory:` aceitou e persistiu `codigo`/`nota_valor` `=1+1`; a exportação real 14.1 devolveu ambas as células sem apóstrofo (`t_2eb000ef`, P2).

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
