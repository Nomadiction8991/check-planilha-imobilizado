## Context

A validação atual combina expressão regular com Carbon, mas não verifica explicitamente que Carbon devolveu exatamente a data informada. Datas inexistentes podem sofrer normalização silenciosa.

## Decisão

Após validar formato, criar a data com `createFromFormat('!Y-m-d', ...)`, verificar erros de parsing e comparar `format('Y-m-d')` com valor original. Manter mensagens específicas por campo e o redirecionamento com query original.

## Testes

Adicionar teste de feature para data inexistente na consulta. Reutilizar cobertura existente para formato inválido, período invertido e exportação.

## Riscos

Nenhuma alteração em dados ou contrato de exportação. Datas válidas continuam no formato ISO aceito pelo input HTML.
