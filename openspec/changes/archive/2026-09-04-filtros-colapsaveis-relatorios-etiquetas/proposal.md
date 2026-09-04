## Why

No mobile o bloco de filtros de relatórios e etiquetas ocupa grande parte da viewport e empurra a lista de resultados para baixo. Produtos já tem filtros colapsáveis no mobile com botão e contador de ativos; relatórios e etiquetas ainda não têm o mesmo comportamento, o que quebra a consistência e piora a usabilidade em telas pequenas.

## What Changes

- Tornar os filtros de `/reports` e `/labels` colapsáveis no mobile (≤860px) com mesmo padrão usado em `/products`: botão "Filtros · N ativos" visível só no mobile, painel colapsado por padrão, expandido no desktop.
- Contar filtros ativos de forma específica por tela (relatórios: administracao_id, estado, comum_id; etiquetas: administracao_id, estado, comum_id, dependencia) e refletir no rótulo do botão.
- Reutilizar o script/CSS de toggle e painel já existentes em `layouts/migration.blade.php`, estendendo para novos atributos sem duplicar lógica.
- Não altera contratos HTTP, paginação, escopo ou submissão automática já existente nas duas telas; apenas a apresentação colapsável no mobile.

## Capabilities

### New Capabilities

- Nenhuma.

### Modified Capabilities

- `relatorios-listagem`: filtros da listagem de relatórios passam a ser colapsáveis no mobile com botão e contador, mantendo compatibilidade desktop.
- `etiquetas-listagem`: filtros de etiquetas passam a ser colapsáveis no mobile com botão e contador, mantendo compatibilidade desktop.

## Impact

Views `resources/views/reports/index.blade.php` e `resources/views/labels/index.blade.php` e layout base `resources/views/layouts/migration.blade.php` (CSS/JS de toggle e painel). Sem mudança de rotas, controllers ou regras de autorização. Comportamento testável por view com assertions de presença de toggle/painel e `aria-expanded`.
