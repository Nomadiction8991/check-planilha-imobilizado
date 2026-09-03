## Context

Consulte `proposal.md` e a especificação de etiquetas. A tela usa uma consulta SQL direta para obter dependências e produtos marcados para impressão. A regra já existente na listagem de produtos combina o estado de edição com a presença de valor exibível na relação editada; a consulta de etiquetas precisa aplicar o mesmo critério em ambos os resultados.

## Goals / Non-Goals

**Goals:**

- Definir uma expressão única de dependência atual para a consulta de opções, filtro e descrição dos produtos.
- Tratar nulo, zero, produto não editado e relação editada sem descrição como fallback para a dependência original.
- Manter a proteção de escopo da igreja e o filtro de impressão existente.
- Cobrir a regressão com dados SQLite que representem valores editados válidos, residuais e inválidos.

**Non-Goals:**

- Alterar a regra geral de classificação das telas de produtos.
- Alterar o formato das etiquetas, a persistência de etiquetas manuais ou as rotas.
- Criar migração, índice ou dependência externa.

## Decisions

### Usar `CASE` condicionado ao estado de edição e à descrição

A dependência atual será calculada com uma expressão condicional: somente um produto com estado editado igual a 1 e descrição editada não vazia poderá usar o vínculo editado; em todos os outros casos será usado o vínculo original. A mesma expressão será aplicada ao identificador e à descrição, garantindo que uma opção nunca aponte para um ID diferente do texto mostrado.

Alternativa considerada: `COALESCE` simples entre os vínculos editado e original. Ela é menor, mas considera vínculo residual em produto não editado e considera relação editada sem descrição como válida, reproduzindo a inconsistência que este change corrige.

### Reutilizar a expressão em todas as partes da consulta

A expressão será mantida como uma variável local no método e interpolada somente em cláusulas SQL já construídas pelo Query Builder, com o ID do filtro continuando parametrizado. Isso evita divergência entre opções, seleção e linhas de resultado.

Alternativa considerada: buscar todos os produtos e resolver a dependência em PHP. Foi rejeitada porque aumenta memória e processamento e torna a filtragem/paginação tardia, além de não aproveitar o banco para restringir o resultado.

### Considerar descrição com espaços como vazia

A validação usará `TRIM` para que uma descrição composta somente por espaços não seja tratada como valor válido. O vínculo original continuará disponível como fallback.

## Risks / Trade-offs

- [Risco] A expressão SQL precisa funcionar no SQLite e no PostgreSQL/MySQL usados pelo projeto. → [Mitigação] Usar apenas `CASE`, `WHEN`, `AND`, `TRIM`, `COALESCE` e comparações portáveis, com teste em SQLite e sem funções específicas de um único banco.
- [Risco] O mesmo ID pode existir em vínculos original e editado, mas com descrição diferente. → [Mitigação] Calcular ID e descrição com a mesma condição, preservando a coerência do seletor.
- [Risco] Relação editada válida sem produto atualmente editado pode ser confundida com dado atual. → [Mitigação] Incluir explicitamente `p.editado = 1` na condição e teste de regressão para valor residual.

## Migration Plan

Nenhuma migração de dados é necessária. Executar primeiro os testes unitários do serviço em ambiente isolado, depois a suíte completa, validar a sintaxe PHP e a saúde da aplicação. O rollback é a reversão do commit, sem alteração de banco.
