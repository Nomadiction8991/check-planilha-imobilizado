## Why

Filtros de auditoria aceitam datas de calendário inexistentes, como 31 de fevereiro. Isso pode retornar resultados errados e passa falsa sensação de filtro aplicado corretamente.

## What Changes

- Rejeitar datas com formato correto, mas calendário inválido.
- Preservar filtros informados ao redirecionar para a tela de auditoria.
- Cobrir validação de datas impossíveis na tela e na exportação.

## Capabilities

### New Capabilities

### Modified Capabilities

- `auditoria`: exigir datas de calendário válidas nos filtros.

## Impact

Afeta validação dos filtros GET da auditoria e sua exportação. Não altera registros, formato de CSV nem persistência de auditoria.
