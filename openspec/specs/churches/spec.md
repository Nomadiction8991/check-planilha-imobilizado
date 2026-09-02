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

### Requirement: Filtro de Igrejas por Estado
O sistema SHALL permitir que o usuário filtre as igrejas cadastradas por Unidade Federativa (UF/Estado) na tela de listagem de igrejas.

#### Scenario: Filtragem por estado específico
- GIVEN que existem igrejas cadastradas com estados diferentes (ex.: SP e PR)
- WHEN o usuário acessa a listagem com o parâmetro `estado=PR`
- THEN apenas as igrejas com `estado = 'PR'` devem ser exibidas na listagem
- AND o seletor de estado deve manter a opção 'PR' selecionada.

#### Scenario: Listagem sem filtro de estado
- GIVEN que o usuário não seleciona nenhum estado (ou seleciona "Todos os estados")
- WHEN a listagem de igrejas for carregada
- THEN todas as igrejas devem ser consideradas, respeitando os demais filtros ativos.

