# Especificação da Padronização de Estados em Produtos

## ADDED Requirements

### Requirement: [ADDED] Passagem de Estados nas Views de Criação e Edição de Produtos
O `LegacyProductController` DEVE passar explicitamente a lista de estados federativos (`states`) ao renderizar as views `products.create` e `products.edit`.

#### Scenario: Visualização do formulário de criação de produto
- GIVEN um usuário autenticado acessando o formulário de cadastro de produto
- WHEN a rota `migration.products.create` é requisitada via GET
- THEN a view `products.create` DEVE receber a chave `states` contendo o mapeamento de estados brasileiros a partir da configuração `brazil.states`

#### Scenario: Visualização do formulário de edição de produto
- GIVEN um usuário autenticado acessando o formulário de edição de produto
- WHEN a rota `migration.products.edit` é requisitada via GET
- THEN a view `products.edit` DEVE receber a chave `states` contendo o mapeamento de estados brasileiros a partir da configuração `brazil.states`
