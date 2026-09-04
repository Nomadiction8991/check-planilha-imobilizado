# Design — filtros colapsáveis em relatórios e etiquetas

## Contexto

Produtos (`/products` e `/products/verification`) já tem filtros colapsáveis no mobile com botão `data-product-filters-toggle` e painel `data-product-filters-panel`, CSS `.product-filters-toggle` e JS em `layouts/migration.blade.php`. Relatórios (`/reports`) e etiquetas (`/labels`) usam `div.filters[data-sticky-filters]` com `form` direto e não têm esse padrão — no mobile o filtro toma a viewport e empurra os resultados.

## Decisões

- Reutilizar CSS/JS existentes e generalizar seletores para cobrir novos painéis. Evitar duplicar estilos ou criar novo script inline nas views.
- Mesma quebra: `max-width: 860px` para mobile, `min-width: 861px` para desktop (consistência com produtos).
- Estado via `dataset.collapsed` + `aria-expanded` no botão; rótulo segue `Filtros` / `Filtros · N ativos` (contagem vinda do servidor como `data-active-count`).
- Botão fora do `form` para não submeter; painel engloba `form` dentro do `div.filters`.
- Contagem específica por tela: relatórios conta `administracao_id`, `estado`, `comum_id`; etiquetas conta `administracao_id`, `estado`, `comum_id`, `dependencia`. Cálculo no Blade a partir de query params já disponíveis na view.

## Alternativas consideradas

- Criar componentes separados por tela: descartado — duplica CSS/JS e quebra consistência.
- Usar `details/summary`: descartado — estilo do botão/contador e comportamento de scroll-into-view já consolidados.

## Mudanças

- `resources/views/reports/index.blade.php`: calcular activeCount no Blade, inserir botão com `data-active-count` e `aria-controls`, envolver `form` em `div` com painel data-attr.
- `resources/views/labels/index.blade.php`: idem (inclui dependencia no count).
- `resources/views/layouts/migration.blade.php`: estender CSS do toggle/painel e JS de sync para reconhecer novos toggles/paineis (além de `data-product-filters-*`).

## Riscos

- Baixo: mudança puramente apresentacional; não altera HTTP, escopo ou paginação. Testes de view validam marcadores.
- Contagem: manter alinhada aos params da tela; sem isso o rótulo ficaria desatualizado mas sem impacto funcional.

## Referências

- Specs delta: `specs/relatorios-listagem/spec.md` e `specs/etiquetas-listagem/spec.md` no change.
