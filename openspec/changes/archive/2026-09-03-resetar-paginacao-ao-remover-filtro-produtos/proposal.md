## Why

Ao remover um filtro pelos chips ativos, a listagem mantém o parâmetro de página atual. Se o filtro anterior tinha poucos resultados, a página pode ficar vazia mesmo existindo produtos na primeira página. A remoção de um critério deve voltar ao início do conjunto resultante para que o usuário veja imediatamente os dados disponíveis.

## What Changes

- Fazer os links de remoção dos chips ativos reiniciarem a paginação na primeira página.
- Fazer o link de limpeza de todos os filtros também remover a página atual.
- Preservar os demais filtros e o caminho da tela ao remover um critério.
- Cobrir listagem e verificação para impedir regressão desse comportamento.

## Capabilities

### New Capabilities

### Modified Capabilities

- `produtos-listagem`: a remoção de filtros ativos deve descartar o parâmetro de paginação para exibir o início do resultado atualizado.

## Impact

A mudança fica restrita à construção das URLs dos chips de filtros ativos nas telas de produtos. Não altera a consulta, o contrato das rotas nem a persistência de dados; afeta apenas a navegação após uma alteração de filtros.
