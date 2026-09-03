## Context

A consulta de produtos já aplica filtros explícitos por igreja, administração e UF, mas os métodos de opções e a consulta sem filtro não reutilizam o escopo de administrações gravado na sessão. O usuário autenticado fornece `administracao_id` e `administracoes_permitidas`; administradores são identificados por `is_admin`. A mudança deve permanecer compatível com o banco legado e com os testes SQLite em memória.

## Goals / Non-Goals

**Goals:**

- Centralizar no browser de produtos a resolução dos IDs de administração visíveis.
- Aplicar o escopo a produtos por meio da relação da igreja, preservando filtros solicitados e paginação.
- Limitar opções de igrejas e dependências ao mesmo conjunto permitido.
- Preservar acesso global para administradores e manter a interface existente.
- Provar o comportamento com testes de unidade do serviço e de renderização da tela.

**Non-Goals:**

- Alterar permissões, autenticação, banco de dados, endpoints de mutação ou o contrato de administração de usuários.
- Implementar uma política nova para tipos de bem ou relatórios nesta mudança.
- Inferir escopo a partir de dados enviados pelo navegador; a sessão continua sendo a fonte de autorização.

## Decisions

### Escopo derivado da sessão

O serviço terá um resolvedor privado que retorna `null` para administradores, ou uma lista normalizada e sem IDs inválidos para usuários restritos. A lista combina `administracoes_permitidas` com a administração principal da sessão, evitando que uma inconsistência de sessão remova a administração primária autorizada. Sem nenhum ID válido, o usuário restrito verá uma consulta vazia, nunca todos os dados.

Alternativa considerada: confiar somente no `administracao_id` filtrado pela URL. Rejeitada porque permite omissão do filtro e não cobre usuários com múltiplas administrações permitidas.

### Consulta de produtos

A consulta aplicará `whereHas('comum')` com `whereIn('administracao_id', ...)` quando houver escopo restrito. O filtro explícito de administração continuará combinando com essa condição, portanto uma administração não permitida produzirá zero resultados. Os demais filtros permanecem independentes.

Alternativa considerada: filtrar IDs de igrejas em PHP. Rejeitada por carregar dados fora do escopo e por perder eficiência na paginação.

### Opções de filtro

`churchOptions()` aplicará o escopo diretamente na relação da igreja. `dependencyOptions()` continuará aceitando uma igreja escolhida, mas também limitará a consulta pela administração da igreja; com uma igreja não permitida, retornará vazio. Para a tela inicial sem igreja selecionada, a consulta continuará retornando dependências apenas de igrejas permitidas.

Alternativa considerada: ocultar somente as opções na Blade. Rejeitada porque links e consultas poderiam continuar apontando para identificadores fora do escopo.

## Risks / Trade-offs

- [Sessão antiga sem lista de permissões] → usar a administração principal como fallback; se também não existir, retornar consulta vazia para evitar vazamento.
- [Schema legado sem `administracao_id` em `comums`] → o escopo não poderá ser aplicado com segurança; o contrato atual do projeto exige essa coluna e os testes devem refletir o schema real.
- [Filtros de URL de usuário restrito] → manter a interseção entre filtro solicitado e escopo da sessão; cobrir explicitamente administração fora do escopo.
- [Opções de dependência com igreja inválida] → retornar coleção vazia, sem buscar dados de outras igrejas.

## Migration Plan

Nenhuma migração de banco é necessária. A implantação consiste em publicar o código após os testes PHPUnit e a verificação de saúde da aplicação. Em caso de regressão, reverter o commit de produto e republicar; não há alteração de dados persistente.
