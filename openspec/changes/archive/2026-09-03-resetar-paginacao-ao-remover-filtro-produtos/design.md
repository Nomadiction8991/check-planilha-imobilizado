## Context

A parcial que renderiza os chips ativos recebe a consulta original da requisição e monta links preservando todas as chaves. As telas de produtos usam `pagina` como nome do parâmetro de paginação, e o helper compartilhado de paginação também mantém os filtros ao navegar. A mudança deve atuar somente nos links de alteração de filtros, sem interferir na paginação normal.

## Goals / Non-Goals

**Goals:**

- Garantir que qualquer URL gerada para remover um filtro comece na primeira página do resultado.
- Garantir que a limpeza completa não carregue filtros antigos nem paginação.
- Manter a mesma solução para listagem e verificação, evitando divergência entre as telas.
- Cobrir o comportamento pelo HTML renderizado, incluindo filtros equivalentes de itens novos.

**Non-Goals:**

- Alterar a consulta de produtos, o tamanho da página ou a ordem dos resultados.
- Alterar os links de paginação ou os filtros de outras telas.
- Criar armazenamento adicional para o estado de navegação.

## Decisions

### Remover `pagina` no mesmo ponto que remove o filtro

A parcial de chips continuará sendo a única responsável por construir a URL de remoção. A função que remove uma chave começará com a consulta atual e retirará `pagina` antes de retirar a chave selecionada; o fluxo especial de "Somente novos" seguirá removendo as duas formas equivalentes. Isso preserva todos os demais critérios e evita que a regra fique duplicada nas views.

Alternativa considerada: fazer o controller ignorar a página quando a requisição viesse de um chip. Rejeitada porque o controller não distingue uma navegação de chip de uma URL digitada e porque a correção precisa ser refletida no href entregue ao usuário.

### Limpeza completa usa a URL base

O link "Limpar todos" já aponta para o caminho atual sem query string. Ele será mantido assim, pois essa construção remove naturalmente filtros e paginação sem depender do conjunto de chaves conhecido pela parcial.

Alternativa considerada: montar a limpeza removendo chave por chave. Rejeitada por ser mais frágil e permitir que novos filtros ou parâmetros de paginação escapem.

### Testes de regressão na camada de feature

Os testes enviarão filtros com `pagina` e verificarão os hrefs renderizados para remoção de um critério e para o indicador de itens novos. O mesmo HTML é usado nas duas telas, então os testes garantirão a presença da parcial e a preservação dos critérios restantes em cada rota.

## Risks / Trade-offs

- [Risco] Um futuro parâmetro de paginação com outro nome pode voltar a ser preservado → [Mitigação] manter `pagina` explícito na regra e atualizar o teste caso o contrato de paginação seja alterado.
- [Risco] A URL base pode perder um contexto necessário da tela → [Mitigação] usar `request()->url()`, preservando o caminho e removendo apenas a query, conforme o comportamento atual de "Limpar todos".

## Migration Plan

Nenhuma migração de dados é necessária. Após os testes de regressão e a validação do change, o deploy normal por push aplicará a alteração; em caso de problema visual, basta reverter o commit de produto e publicar novamente.
