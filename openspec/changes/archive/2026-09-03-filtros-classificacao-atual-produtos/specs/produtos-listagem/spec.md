## ADDED Requirements

### Requirement: Busca e filtros usam a classificação atual do produto
O sistema SHALL aplicar a busca geral e os filtros de dependência e tipo de bem sobre a classificação atual do produto. Para produtos editados, os valores editados válidos DEVEM ser considerados; quando não houver valor editado válido, a relação original DEVE ser usada como fallback. O sistema SHALL preservar o escopo de acesso vigente.

#### Scenario: Busca encontra produto pelo tipo editado
- GIVEN um produto editado com tipo original "CADEIRA" e tipo atual "MESA"
- WHEN o usuário consulta a busca geral pelo termo "MESA"
- THEN o produto é retornado

#### Scenario: Busca encontra produto pela dependência editada
- GIVEN um produto editado com dependência original "SALÃO" e dependência atual "SECRETARIA"
- WHEN o usuário consulta a busca geral pelo termo "SECRETARIA"
- THEN o produto é retornado

#### Scenario: Busca não usa classificação substituída como se fosse atual
- GIVEN um produto editado com tipo original "CADEIRA" e tipo atual "MESA"
- WHEN o usuário consulta a busca geral pelo termo "CADEIRA"
- THEN o produto não é retornado por causa do tipo original substituído

#### Scenario: Filtro de tipo usa o tipo editado
- GIVEN um produto editado com tipo original de identificador 4 e tipo atual de identificador 7
- WHEN o usuário filtra por tipo de bem 7
- THEN o produto é retornado

#### Scenario: Filtro de dependência usa a dependência editada
- GIVEN um produto editado com dependência original de identificador 2 e dependência atual de identificador 3
- WHEN o usuário filtra por dependência 3
- THEN o produto é retornado

#### Scenario: Filtros usam o original quando o valor editado é inválido
- GIVEN um produto editado com relação original válida e relação editada ausente ou sem valor de exibição
- WHEN o usuário consulta por essa classificação original
- THEN o produto é retornado usando a relação original como fallback
