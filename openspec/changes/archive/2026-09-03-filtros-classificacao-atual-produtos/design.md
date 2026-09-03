## Context

A consulta de produtos já carrega as relações original e editada para renderizar a classificação vigente, mas os predicados da busca e dos filtros ainda consultam somente as colunas e relações originais. A mudança deve manter os parâmetros existentes e atender ao SQLite usado nos testes e ao PostgreSQL em produção.

## Goals / Non-Goals

**Goals:**

- Definir uma única regra de consulta para classificação vigente: relação editada somente quando o produto está marcado como editado e a relação possui valor de exibição; caso contrário, relação original.
- Aplicar essa regra à busca textual e aos filtros por identificador.
- Manter agrupados os predicados OR da busca para não alterar a combinação com escopo, estado, igreja e status.
- Evitar consulta por produto na renderização e preservar a autorização já aplicada ao construtor.

**Non-Goals:**

- Alterar o modelo de dados ou migrar registros existentes.
- Alterar os nomes ou os formatos dos parâmetros HTTP.
- Alterar a busca de código, nome do produto, complemento ou os filtros de administração, igreja, estado e status.
- Recalcular ou persistir valores editados durante uma consulta.

## Decisions

### Usar `whereHas` com predicados de validade da edição

A decisão é expressar a classificação atual diretamente no construtor Eloquent: quando `editado = 1` e a relação editada tem descrição ou código não vazio, o predicado consulta a relação editada; no ramo alternativo, consulta a relação original. Isso evita depender de `COALESCE` entre tabelas relacionadas, que não representa corretamente a regra de validade quando a relação existe mas não tem valor de exibição, e mantém a autorização dentro da mesma query.

A alternativa rejeitada é filtrar a coleção depois da paginação. Ela produziria totais e páginas incorretos, além de carregar produtos que deveriam ser descartados. Também foi rejeitada a duplicação em controller ou Blade, pois deixaria a busca diferente do filtro e aumentaria o risco de N+1.

### Reutilizar grupos de predicados, sem SQL específico de banco

A busca será organizada em grupos Eloquent com `where`/`orWhere` e `whereHas`, sem expressões de concatenação ou funções específicas do PostgreSQL. Os filtros por ID usarão grupos equivalentes com `whereHas`, permitindo que o framework gere SQL compatível com SQLite e PostgreSQL.

A alternativa rejeitada é usar `whereRaw` com `CASE` ou `COALESCE` envolvendo subconsultas: embora mais curta, essa opção aumenta a diferença entre os bancos e dificulta a prova de que uma edição inválida cai no original.

### Cobertura de comportamento no serviço

Os testes semeiam produto, tipo e dependência originais e editados, verificando busca por valores atuais, exclusão de valores substituídos, filtros por identificador e fallback. A consulta será exercitada no banco de teste real do projeto, sem alterar dados de produção.

## Risks / Trade-offs

- [Risco] Relações legadas podem usar `0`, `NULL` ou descrições vazias para indicar ausência → [Mitigação] O ramo editado exige `editado = 1` e código ou descrição não vazios; os testes cobrem relação ausente e sem valor.
- [Risco] A busca pode perder o agrupamento e escapar do escopo de acesso → [Mitigação] Manter todos os predicados de classificação dentro do grupo da busca e preservar os testes de escopo existentes.
- [Risco] Uma relação editada válida pode ser referenciada fora da igreja do produto no legado → [Mitigação] Esta mudança não amplia o escopo do relacionamento; o identificador é usado apenas para classificação do produto já autorizado, e a regra segue o estado gravado.

## Migration Plan

Nenhuma migração de dados ou mudança de rota é necessária. Após os testes unitários e de integração do serviço, a publicação normal por push atualizará a aplicação; rollback consiste em reverter o commit caso a consulta apresente regressão.
