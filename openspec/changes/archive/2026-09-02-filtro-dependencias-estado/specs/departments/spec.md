## ADDED Requirements

### Requirement: Filtrar dependências por estado (UF) da igreja
O sistema SHALL permitir filtrar dependências pela Unidade Federativa (UF) da igreja vinculada na consulta de dependências.

#### Scenario: Filtragem por estado existente
- GIVEN dependências cadastradas vinculadas a igrejas em 'SP' e 'RJ'
- WHEN o usuário consulta a listagem de dependências filtrando por estado 'SP'
- THEN apenas as dependências vinculadas a igrejas de 'SP' devem ser retornadas

#### Scenario: Estado não informado
- GIVEN dependências de múltiplos estados
- WHEN o usuário consulta a listagem sem especificar o estado
- THEN todas as dependências devem ser retornadas respeitando os demais filtros
