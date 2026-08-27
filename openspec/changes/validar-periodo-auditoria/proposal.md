## Why

Filtros de período inválidos hoje são ignorados silenciosamente, fazendo a tela e a exportação exibirem resultados que não correspondem ao pedido. Usuário precisa receber orientação clara para corrigir data antes de consultar auditoria.

## What Changes

- Validar datas inicial e final no filtro de auditoria usando formato ISO aceito pelo campo date.
- Exibir mensagem clara e manter valores informados quando período for inválido.
- Impedir consulta e exportação com datas inválidas.

## Capabilities

### New Capabilities

### Modified Capabilities

- `auditoria`: filtros de período devem rejeitar datas inválidas em vez de ignorá-las.

## Impact

Controlador e tela de auditoria; sem alteração de persistência, contrato de exportação ou formato CSV.
