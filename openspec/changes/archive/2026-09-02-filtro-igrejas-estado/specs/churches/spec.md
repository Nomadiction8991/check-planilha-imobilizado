## ADDED Requirements

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
