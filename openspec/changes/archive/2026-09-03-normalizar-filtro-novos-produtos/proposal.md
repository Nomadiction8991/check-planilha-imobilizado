## Why

A consulta de produtos aceita duas formas legadas para representar “somente novos”: `status=novos` e `somente_novos=1`. Quando ambas aparecem na mesma URL, a interface conta e exibe o mesmo critério duas vezes, confundindo o usuário no mobile e tornando a remoção do filtro incompleta. A normalização evita essa duplicidade sem quebrar links legados.

## What Changes

- Tratar `status=novos` e `somente_novos=1` como um único critério de filtro.
- Exibir apenas um indicador “Somente novos” quando as duas formas estiverem presentes.
- Remover as duas chaves relacionadas ao selecionar esse indicador, evitando que o filtro reapareça.
- Gerar links de paginação com uma representação canônica quando o status for “novos”.
- Cobrir a normalização com testes unitários e de renderização da tela.

## Capabilities

### New Capabilities

<!-- Nenhuma capacidade nova; a melhoria altera um requisito existente. -->

### Modified Capabilities

- `produtos-filtros-colapsaveis`: esclarecer que o contador e os indicadores de filtros devem deduplicar as duas formas equivalentes do filtro de itens novos.

## Impact

A mudança afeta o DTO de filtros de produtos, a serialização dos parâmetros usados na paginação e o componente visual de filtros ativos nas telas de produtos. Não altera a consulta de dados nem o significado dos filtros existentes; URLs antigas continuam sendo aceitas.