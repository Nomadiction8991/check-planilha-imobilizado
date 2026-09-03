## 1. Envoltório e botão (views)

- [ ] 1.1 Em `resources/views/products/index.blade.php`, envolver o bloco `form` + `@include('products.partials.active-filter-chips')` em `div[data-product-filters-panel]` e inserir acima um botão `type="button" data-product-filters-toggle` com `aria-expanded` e rótulo “Filtros · N ativos” (contagem via helper PHP no Blade que normaliza `busca`/`nome`/`codigo`), mantendo `div.filters[data-sticky-filters]` como âncora externa do pin.
- [ ] 1.2 Aplicar a mesma estrutura em `resources/views/products/verification.blade.php` (mesmo wrapper e botão, IDs distintos se necessário).

## 2. Estilo e comportamento (layout)

- [ ] 2.1 Em `resources/views/layouts/migration.blade.php`, adicionar CSS: toggle com estilo capsular/outline aderente ao tema; em `@media (max-width: 860px)` esconder painel quando `[data-collapsed="true"]` e mostrar toggle; em `@media (min-width: 861px)` forçar painel visível e ocultar toggle; preservar `display:block` do pin sem conflito.
- [ ] 2.2 No mesmo layout, adicionar script progressivo: ao `DOMContentLoaded`, para cada painel, inicializar `data-collapsed` pelo `matchMedia("(max-width: 860px)")`, alternar `data-collapsed`/`aria-expanded`/rótulo no clique, e em resize para desktop remover colapso; sem `localStorage`, sem dependências, com guarda `querySelector` para páginas sem filtros.

## 3. Validação

- [ ] 3.1 Rodar `php -l` nos arquivos PHP/Blade alterados.
- [ ] 3.2 Validar OpenSpec: `openspec validate --change filtros-colapsaveis-produtos-mobile --strict`.
- [ ] 3.3 Validar manualmente: `curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:8084/products` e `/products/verification` retornam 200; suíte relevante `php artisan test --filter=LegacyProduct` verde.
