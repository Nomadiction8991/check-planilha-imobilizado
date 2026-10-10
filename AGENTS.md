# AGENTS.md

Regras para qualquer agente que trabalhe neste repositório. O alinhamento é obrigatório
antes de abrir PR — releia este arquivo a cada sessão.

## Stack

- **PHP 8.3+ / Laravel 13** — backend, rotas em `routes/`, lógica em `app/`
  (`Features/`, `Services/`, `Repositories/`, `DTO/`, `ValueObjects/`, `Contracts/`, `Core/`, `Support/`)
- **Blade + Tailwind CSS 4** (via Vite) — UI em `resources/views/`
- **PHPUnit** — testes em `tests/Unit` e `tests/Feature`
- **PHPStan** (nível definido em `phpstan.neon.dist`) — análise estática
- **Docker / docker-compose** — ambiente de execução
- **league/csv** — leitura de planilhas/CSV (importação)

Convenção de código existente: nomes de classe em inglês, rotas e views em português
(ex.: `resources/views/importacao/`). Novas telas seguem o mesmo padrão.

## Regras inegociáveis

### 1. TDD — teste primeiro, sempre

**Nenhuma feature, correção ou refactor entra sem teste.**

O ciclo é **RED → GREEN → REFACTOR**, nesta ordem:

1. **RED** — escreva o teste que falha. Ele deve falhar pelo motivo certo
   (não por erro de sintaxe ou fixture faltando).
2. **GREEN** — Implemente o mínimo para o teste passar. Nada de "já vou
   deixar pronto o resto".
3. **REFACTOR** — limpe a implementação com os testes verdes como rede de segurança.

Regras práticas:

- Teste **comportamento**, não implementação. Um teste que quebra a cada
  refactor interno está errado.
- Toda branch de feature começa por um teste;
  `git log` deve mostrar o teste antes do código.
- Cobre os caminhos de erro, não só o feliz — importação de planilha
  inválida, permissão negada, arquivo vazio, timeout.
- **Bug fix:** primeiro escreva o teste que reproduz o bug (ele falha),
  depois corrija. Sem exceção — é o que garante que o bug não volta.
- Nenhum teste pode ser `skip`, `markTestIncomplete` ou comentado
  para "passar". Se não dá para testar agora, a task não está pronta.

### 2. Testes passando antes de entregar

Antes de dizer "pronto" ou abrir PR, rode e mostre a saída:

```bash
composer test          # phpunit (Unit + Feature)
composer phpstan       # análise estática, sem erros novos
npm run build          # assets compilam (se houve mudança de UI)
```

- Os três precisam passar. "Passa local" sem evidência não conta.
- PHPStan: use `phpstan-baseline.neon` para legado,
  mas **não cresça a baseline** — corrija o que for novo.
- Teste quebrado em código que você não tocou: relate, não ignore.
- O commit só existe com a suíte verde.

### 3. UI — simplicidade mobile-first

O usuário final usa **celular**. Toda tela é projetada primeiro para
a tela pequena, depois adaptada para desktop.

- **Mobile-first de verdade:** escreva o CSS/pensamento a partir de 360px.
  Desktop é a exceção, não o ponto de partida.
- **Um toque, uma ação.** Nada de menu escondido atrás de três cliques.
  A ação principal de cada tela fica visível sem scroll.
- **Alvos de toque** com pelo menos 44x44px, com espaçamento entre eles.
- **Menos é mais:** se um campo, botão ou texto pode sair sem perder
  função, ele sai. Remover > adicionar.
- **Formulários:** um papel por tela. Agrupe o que é relacionado,
  use os tipos de input corretos (`tel`, `email`, `number`),
  e mostre erro junto ao campo, não só no topo.
- **Feedback imediato:** loading, sucesso e erro precisam ser perceptíveis
  no celular — botão desabilita enquanto processa, e o usuário
  sabe que a importação está rodando.
- **Texto legível:** mínimo 16px no corpo, contraste acessível,
  e nada de texto cortado em telas estreitas.
- **Navegação:** previsível. O usuário sempre sabe onde está
  e como voltar. Nada de fluxo que só funciona com mouse.
- **Não invente dependência nova de frontend** sem necessidade —
  o projeto já usa Tailwind; resolva com ele.

**Teste de UI:** toda tela nova ou alterada precisa de cobertura em
`tests/Feature` verificando o que o usuário vê (status HTTP, elementos
essenciais, mensagens de erro). `resources/views/layouts/migration.blade.php`
já tem padrão de responsividade — use como referência.

### 4. Importação de planilhas (domínio central)

É o coração do sistema, e o ponto mais sensível:

- Toda mudança em importação exige teste com **arquivo real** de amostra
  (`.xlsx`, `.xls`, `.csv`) — inclusive o caminho de falha.
- Valide pelo menos: **arquivo vazio, coluna faltando, encoding estranho,
  linha gigante, tipo inesperado na célula**. Não confie só no happy path.
- Erros de importação são **mensagem acionável para o usuário final**
  ("linha 42: coluna 'Patrimônio' vazia"), não stack trace.
- `erros-encontrados.md` documenta erros conhecidos — quando resolver um,
  atualize o documento no mesmo commit.

## Como trabalhar aqui

```bash
composer setup     # instala dependências, .env, migrate, build
composer test      # suíte completa
composer phpstan   # análise estática
composer dev       # servidor + queue + logs + vite
```

- Commits em português, convencionais (`feat:`, `fix:`, `test:`, `refactor:`).
- Uma tarefa por branch; PR descreve **o que mudou e como foi testado**.
- `docs/passo-a-passo/` documenta os fluxos — quando um fluxo muda,
  o passo a passo muda no mesmo commit.

## Automação (agentes/hunter)

### Auto-commit
- Toda entrega concluída termina com commit + push.
- Trabalho automatizado leva `(auto)` no FINAL do título, ex.: `fix(import): valida CNPJ vazio (auto)`.
- Padrão da skill user-commit: subject ≤80 chars, corpo SEMPRE obrigatório em linguagem simples (sem jargão/funções/stacks, ≤400 chars), sem rodapé de IA.
- Nunca commite segredos (.env, tokens, chaves).

### Sync antes de mexer
- Antes de qualquer alteração: `git fetch` + `git pull` (ou rebase da branch) e confira `git status`/`git log`.
- Nunca sobrescreva commit alheio nem recorrija o já corrigido — se a base mudou, reavalie o plano.
- Working tree sujo: NUNCA pare a execução. Identifique o pendente (`git status`/`git diff`/`git stash list`): sujeira de ferramenta (`.codegraph/`, `.serena/`, cache) vai para o `.gitignore`; seu próprio rascunho, `git stash`; trabalho de outro, não commite por cima — isole em worktree/branch e continue a tarefa.
