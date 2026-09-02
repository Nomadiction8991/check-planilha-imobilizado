# Delta Specs: Filtro por Administração em Produtos

## ADDED Requirements

### Requirement: Filtragem por Administração na Listagem e Verificação de Produtos
The system SHALL allow filtering products by administration ID (`administracao_id`), matching products whose linked church belongs to the specified administration.

#### Scenario: Filtragem de produtos com ID de administração válido
- GIVEN produtos vinculados a igrejas de diferentes administrações
- WHEN a listagem (`/products`) ou tela de verificação (`/products/verification`) for consultada com o parâmetro `administracao_id`
- THEN apenas os produtos pertencentes a igrejas daquela administração DEVEM ser retornados na paginação

#### Scenario: Consulta sem filtro de administração
- GIVEN produtos cadastrados no sistema
- WHEN o parâmetro `administracao_id` não for informado ou for nulo/zero
- THEN todos os produtos ativos DEVEM ser retornados respeitando os demais filtros ativos

### Requirement: Busca Progressiva no Seletor de Administração nas Views de Produtos
The UI SHALL provide an interactive search input and select for administration filtering with instant client-side filtering and accessible feedback on `/products` and `/products/verification`.

#### Scenario: Filtragem interativa das opções de administração
- GIVEN a tela de listagem de produtos ou de verificação com o seletor de administrações
- WHEN o usuário digitar texto no campo de busca de administração
- THEN as opções não correspondentes DEVEM ser ocultadas e o contador/status acessível DEVE atualizar adequadamente
