# Design — Filtros colapsáveis em produtos no mobile

## Context

Ver `proposal.md`. As telas `products/index` e `products/verification` renderizam o formulário de filtros dentro de `div.filters[data-sticky-filters]` em `layouts/migration.blade.php` (estilos globais + comportamento de fixação/pin). A feature recente de chips (`products/partials/active-filter-chips.blade.php`) já está integrada logo abaixo do form. Para mobile não há regra de colapso ainda.

## Goals / Non-Goals

**Goals:** reduzir altura inicial no mobile, mantendo acesso total aos filtros; desktop idêntico a hoje; sem mudar controller/service/rotas; acessível (`aria-expanded`).

**Non-Goals:** refazer layout de filtros, mover estado para backend/session, adicionar libs.

## Decisions

- Marcação mínima nas views: envolver `form` + `@include('products.partials.active-filter-chips')` em um wrapper com `data-product-filters-panel` e preceder com um botão `data-product-filters-toggle` (rótulo “Filtros · N ativos”). Botão fora do `<form>` para não submeter.
- Estilo/visibilidade em `layouts/migration.blade.php`: em `@media (max-width: 860px)` o painel é `display:none` quando `[data-collapsed="true"]`; em `≥861px` força `display:block` e esconde o toggle (match do breakpoint já usado no `migration.blade.php` para `.filters-primary`). Evita nova folha.
- Script progressivo inline no próprio layout (sem build): inicializa ao `DOMContentLoaded`, encontra cada `[data-product-filters-panel]` na página (index ou verification — só um por página), lê contagem de filtros ativos do `request()->query()` já exposta no Blade (computar no PHP e passar como `data-active-count`), define estado inicial `collapsed=true` apenas no mobile (matchMedia), e alterna `data-collapsed` + `aria-expanded` + texto do botão ao clicar. Em resize para desktop, remove colapso.
- Contagem no PHP: contar chaves relevantes em `request()->query()` (`administracao_id`, `comum_id`, `estado`, `busca`/`nome`/`codigo`, `dependencia_id`, `tipo_bem_id`, `status`, `somente_novos`). Normalizar `busca` para cobrir aliases `nome`/`codigo`.

**Alternativas consideradas:** `<details>/<summary>` nativo — descartado por conflito com grid/flex existente dos `.filters`; estado em `localStorage` — descartado por esconder filtros recém-aplicados após reload.

## Risks / Trade-offs

- Toggle confundido com submit → mitigação: `type="button"` e fora do form.
- Pin/fixação (`data-sticky-filters`) com painel colapsado → manter `data-sticky-filters` no wrapper externo; painel interno colapsável não afeta cálculo de `is-pinned`.
- SSR sem JS: filtros ainda acessíveis pois o fallback sem JS mostra painel expandido (script só colapsa quando JS roda).

## Migration Plan

Deploy único; sem migração. Rollback = reverter wrapper/botão/estilos/script.

## Open Questions

- Nenhuma.
