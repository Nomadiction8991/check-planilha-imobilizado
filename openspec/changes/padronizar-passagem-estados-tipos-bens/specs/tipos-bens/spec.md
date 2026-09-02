# Especificação Delta: Padronização de Estados nas Views de Tipos de Bens

## ADDED Requirements

### Requirement: Disponibilizar lista de estados nas views de criação e edição de Tipos de Bens
O controller `LegacyAssetTypeController` SHALL disponibilizar o array de estados (`states`) carregado a partir de `config('brazil.states', [])` para as views `asset-types.create` e `asset-types.edit`.

#### Scenario: Visualizar formulário de criação de tipo de bem
- GIVEN um usuário autenticado acessando a tela de cadastro de tipo de bem (`migration.asset-types.create`)
- WHEN a view `asset-types.create` for renderizada
- THEN a view SHALL ter acesso à variável `states` contendo os estados brasileiros configurados

#### Scenario: Visualizar formulário de edição de tipo de bem
- GIVEN um usuário autenticado acessando a tela de edição de tipo de bem (`migration.asset-types.edit`)
- WHEN a view `asset-types.edit` for renderizada
- THEN a view SHALL ter acesso à variável `states` contendo os estados brasileiros configurados
