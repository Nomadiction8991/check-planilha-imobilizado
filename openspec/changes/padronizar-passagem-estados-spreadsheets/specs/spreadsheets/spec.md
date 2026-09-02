# Especificação da Padronização de Estados em Spreadsheets

## ADDED Requirements

### Requirement: [ADDED] Passagem de Estados na View de Importação
O `SpreadsheetImportController` DEVE passar explicitamente a lista de estados federativos (`states`) ao renderizar a view `spreadsheets.import`.

#### Scenario: Visualização do formulário de importação de planilhas
- GIVEN um usuário autenticado acessando a tela de importação de planilhas
- WHEN a rota `migration.spreadsheets.create` é requisitada
- THEN a view `spreadsheets.import` DEVE receber a chave `states` contendo o mapeamento de estados brasileiros
