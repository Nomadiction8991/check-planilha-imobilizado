# Especificação Delta: Filtro de Administração nas Etiquetas

## ADDED Requirements

### Requirement: Filtro por administração na seleção de congregações para etiquetas
O sistema SHALL permitir filtrar as congregações disponíveis na tela de etiquetas por administração informada (`administracao_id`) e fornecer as opções de administração para o formulário.

#### Scenario: Listagem de opções de administração na tela de etiquetas
- GIVEN que existem administrações e congregações cadastradas
- WHEN o usuário acessa a tela de etiquetas (`/labels`)
- THEN a view DEVE receber a lista de administrações e as congregações correspondentes

#### Scenario: Filtragem dinâmica de administração no select de etiquetas
- GIVEN a presença do select de administrações na tela de etiquetas
- WHEN o usuário digita no campo de busca de administração
- THEN as opções do select de administração DEVEM ser filtradas instantaneamente sem recarregar a página
