## Why

Os filtros de administração e igreja ajudam a localizar opções grandes, mas hoje funcionam apenas como busca visual. Quando o usuário limpa um desses campos, a seleção previamente escolhida pode permanecer no seletor e ser enviada junto com a próxima busca, contrariando a expectativa de que a limpeza também remova o critério aplicado. O comportamento precisa ficar consistente com a busca geral e evitar resultados surpreendentes no fluxo mobile.

## What Changes

- Limpar a busca local de administração deve restaurar todas as opções e remover a administração selecionada, quando ela não estiver mais visível.
- Limpar a busca local de igreja deve restaurar todas as opções e remover a igreja selecionada, quando ela não estiver mais visível.
- A limpeza local deve disparar a atualização automática da listagem somente quando o valor submetido realmente mudar.
- Manter a busca local como filtro de apresentação, sem alterar a consulta do servidor enquanto o usuário apenas digita.
- Preservar as demais opções e os critérios já selecionados nas telas de produtos e verificação.

## Capabilities

### New Capabilities

### Modified Capabilities

- `produtos-listagem`: tornar a limpeza das buscas auxiliares de administração e igreja coerente com a remoção do critério selecionado.

## Impact

A mudança afeta os controles JavaScript dos filtros nas telas de listagem e verificação de produtos e seus testes de renderização/comportamento. Não altera rotas, contratos de API, persistência ou dependências externas.
