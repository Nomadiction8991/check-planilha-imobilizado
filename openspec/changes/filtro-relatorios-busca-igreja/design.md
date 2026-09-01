# Design: Busca filtrável de igreja na lista de relatórios

## Context

`reports/index.blade.php` hoje rende um `<select name="comum_id">` com todas as igrejas do `churchOptions()` (codigo - descricao). Mesmo problema já resolvido em `products/index`, `products/verification` e `public/access` com padrão de busca client-side; replicar aqui para consistência e usabilidade mobile.

## Goals

- Filtro 100% client-side, sem request adicional; preserva `name="comum_id"` e submit GET existente.
- Mensagem acessível (`role="status"`, `aria-live="polite"`) quando nenhum resultado casa.
- Desabilita select enquanto não há match para evitar submit de estado inválido.

## Decisions

- Reaproveitar IDs/atributos do padrão de produtos: `data-reports-church-search` / `data-reports-church-select` / `data-reports-church-status`; lógica JS inline auto-contida (sem asset extra) igual ao já validado em produtos.
- Manter placeholder `Selecione` (value="") sempre visível quando há resultados; ocultar/desabilitar apenas opções não correspondentes.
- Nenhuma mudança em `LegacyReportService` / controller — só view.

## Non-Goals

- Virtualização de lista, paginação de igrejas ou debounce de busca (volume de igrejas cabe no DOM atual).

## Consequences

- Testes de feature validam presença do input/select/status na resposta HTML de `GET /reports`; testes JS unitários não necessários (lógica trivial e já coberta em produtos).
