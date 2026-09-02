# Design: Busca filtrável de administração na lista de usuários

## Context

`resources/views/users/index.blade.php` renderiza um `<select name="administracao_id">` com todas as administrações (`#id - descricao`) sem filtro. Mesmo problema já resolvido em `products/index`, `products/verification`, `reports/index`, `departments/index`, `labels/index` e `public/access` com padrão de busca client-side; replicar aqui para consistência e usabilidade mobile. Ver `proposal.md` para motivação.

## Goals / Non-Goals

**Goals:**
- Busca client-side sem round-trip; preservar name `administracao_id` e GET.
- Acessibilidade (label, aria-controls, status role).
- Placeholder "Todas" sempre selecionável.

**Non-Goals:**
- Alterar query/filtragem no backend; paginação; ordenação.

## Decisions

- **Decision: Reaproveitar padrão de reports/products** — mesmo HTML/JS leve (input type=search + script inline IIFE com querySelector em data-attributes). Evita nova lib; diff mínimo e revisão rápida.
  - Alternativa: componente Blade compartilhado — rejeitada (overhead de abstração para 1 select; follow-up possível quando houver mais telas).
- **Decision: data attributes `data-users-admin-search/select/status`** — isolamento por tela, sem colisão com outros filtros.
- **Decision: hidden+disabled para opções não correspondentes** — impede seleção via teclado de opção oculta; placeholder nunca ocultado.
- **Decision: "Todas" (value="") continua selecionável** — quando filtro não corresponde, select desabilitado informa mas submit GET sem `administracao_id` equivale a "Todas" (mesmo contrato de products/reports).

## Risks / Trade-offs

- **Muitas administrações no DOM** → tradeoff aceito: lista já vem inteira hoje; filtro só melhora navegação. Virtualização fica para follow-up se virar gargalo.
- **Select desabilitado e submit** → Quando desabilitado por "sem resultados", navegador não envia `administracao_id`; comportamento casa com "Todas" — aceitável. Mensagem orienta limpar busca.

## Migration Plan

- Alteração só em view; sem migration. Rollback = reverter blade. Deploy via push main; validar com curl 200 em /login e /users autenticado.

## Open Questions

- Nenhuma.
