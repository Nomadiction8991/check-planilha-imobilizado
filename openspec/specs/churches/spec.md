# churches Specification

## Purpose
TBD - created by archiving change filtro-igrejas-busca-administracao. Update Purpose after archive.
## Requirements
### Requirement: Filtro por administração na listagem de igrejas
O sistema SHALL permitir filtrar a listagem de igrejas por administração selecionada (`administracao_id`), além da busca por texto.

#### Scenario: Filtragem com administração selecionada
- GIVEN que existem igrejas pertencentes a diferentes administrações
- WHEN o usuário submete a listagem de igrejas com o parâmetro `administracao_id`
- THEN apenas as igrejas vinculadas à administração informada DEVEM ser retornadas

#### Scenario: Busca em tempo real de administrações no select
- GIVEN a presença do select de administrações na listagem de igrejas
- WHEN o usuário digita no campo de busca de administração
- THEN as opções do select DEVEM ser filtradas instantaneamente sem recarregar a página

