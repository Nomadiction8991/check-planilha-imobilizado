# Design — Chips de filtros ativos em produtos

## Context

Listagem e verificação já particionam `ProductFilters` e expõem `filters`, `churches`, `administrations`, `dependencies`, `assetTypes`, `states` e `statusOptions`. Ver discussão em `proposal.md`. A paginação preserva query via `->appends($filters->toQuery())`.

## Goals / Non-Goals

**Goals:** chip bar puramente de apresentação, sem mudar serviço/rota/contrato; rótulos legíveis vindos de dados já em tela; remoção seletiva via link GET.

**Non-Goals:** novo endpoint, JS complexo, reescrita do bloco de filtros, alterar `ProductFilters`/browser service.

## Decisions

- Partial Blade `products/partials/active-filter-chips.blade.php` reutilizado por `index` e `verification`. `index`/`verification` passam as mesmas variáveis já disponíveis; partial resolve rótulos por lookup nas coleções (`administrations`, `churches`, etc.) e `config('brazil.states')`/`statusOptions`.
- Links de remoção = `request()->fullUrlWithQuery` com o parâmetro do filtro zerado/removido + preservação dos demais via `request()->query()`; preferir construção explícita de query sem o parâmetro (evita `fullUrlWithQuery` com valor vazio em alguns casos).
- Sem JS obrigatório: remoção é navegação GET. Estilo em CSS inline scoped da partial (capsulas).
- Acessibilidade: container `role="status" aria-live="polite"`, botões `aria-label="Remover filtro …"`.

**Alternativas consideradas:** componente Vue/React e histórico pushState — descartados por sobrecarga para requisito apenas visual/GET.

## Risks / Trade-offs

- Rótulo não resolvido (ex.: ID sem match na coleção limitada ao escopo) → fallback para o ID cru ou "Desconhecido".
- Quebra de layout mobile com muitos chips → `flex-wrap` + `gap`.

## Migration Plan

Deploy único; sem migração. Rollback = reverter partial + includes.
