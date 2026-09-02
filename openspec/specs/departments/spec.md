# departments Specification

## Purpose
TBD - created by archiving change filtro-dependencias-busca-administracao. Update Purpose after archive.
## Requirements
### Requirement: Filtragem por Administração em Dependências
The system SHALL allow filtering departments by administration ID (`administracao_id`), matching departments whose linked church belongs to the specified administration.

#### Scenario: Filtragem de dependências com ID de administração válido
- GIVEN dependências vinculadas a igrejas de diferentes administrações
- WHEN a listagem de dependências for consultada com o parâmetro `administracao_id`
- THEN apenas as dependências pertencentes a igrejas daquela administração DEVEM ser retornadas na paginação

#### Scenario: Listagem sem filtro de administração
- GIVEN dependências cadastradas no sistema
- WHEN o parâmetro `administracao_id` não for informado ou for nulo/zero
- THEN todas as dependências DEVEM ser retornadas respeitando os demais filtros ativos

### Requirement: Busca Progressiva no Seletor de Administração na View de Dependências
The UI SHALL provide an interactive search input and select for administration filtering with instant client-side filtering and accessible feedback.

#### Scenario: Filtragem interativa das opções de administração
- GIVEN a tela de listagem de dependências com o seletor de administrações
- WHEN o usuário digitar texto no campo de busca de administração
- THEN as opções não correspondentes DEVEM ser ocultadas e o contador/status acessível DEVE atualizar adequadamente

