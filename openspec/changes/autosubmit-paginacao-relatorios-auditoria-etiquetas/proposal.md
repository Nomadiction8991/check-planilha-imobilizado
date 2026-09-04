## Why

Os filtros automáticos de produtos já reiniciam a paginação ao mudar um critério (change anterior `produtos-autosubmit-reinicia-paginacao`). Relatórios, auditoria e etiquetas também submetem filtros automaticamente por mudança de selects ou busca, mas não limpam o parâmetro de página. Em páginas avançadas, a troca de filtro pode manter `page`/`pagina` antigo e devolver lista vazia ou paginação inexistente.

## What Changes

- Alinhar o JavaScript de autosubmit de `resources/views/reports/index.blade.php`, `resources/views/audits/index.blade.php` e `resources/views/labels/index.blade.php` com o padrão de produtos: antes da submissão automática, limpar e desabilitar campos `page`/`pagina` (mesmo que não existam como input visível, o GET não deve carregar página anterior).
- Em etiquetas, substituir o `resetPage()` baseado em `window.history.replaceState` por limpeza e desativação de campos do formulário (compatível com o padrão validado em produtos e no partial genérico).
- Manter o partial genérico `resources/views/partials/filter-autosubmit.blade.php` já correto como referência.

## Capabilities

### New Capabilities

- Nenhuma.

### Modified Capabilities

- `relatorios-listagem`: autosubmit passa a reiniciar paginação ao mudar administração, estado ou igreja.
- `auditoria`: autosubmit passa a reiniciar paginação ao mudar administração, módulo, datas ou busca.
- `etiquetas-listagem`: autosubmit passa a reiniciar paginação ao mudar administração, estado, igreja ou dependência, e corrige o reset de página.

## Impact

Afeta apenas JavaScript de autosubmit nas três views citadas. Não altera contratos HTTP, serviços ou paginação no servidor. Risco baixo: comportamento já validado em produtos.
