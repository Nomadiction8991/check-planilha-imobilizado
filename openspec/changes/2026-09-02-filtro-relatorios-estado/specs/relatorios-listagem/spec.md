# Delta Spec: Filtro por Estado na Seleção de Relatórios

## ADDED Requirements

### Requirement: [ADDED] Filtrar opções de igrejas por estado na listagem de relatórios
O sistema SHALL permitir que o usuário filtre a lista de igrejas pelo estado (UF) no seletor de relatórios.

#### Scenario: Filtrando igrejas por estado
- GIVEN um usuário autenticado na tela de relatórios
- WHEN o usuário seleciona uma UF (ex: "MT") no filtro de estado
- THEN o seletor de igrejas exibe apenas as igrejas correspondentes ao estado informado
- AND a seleção de estado é preservada na interface

#### Scenario: Sem filtro de estado
- GIVEN um usuário na tela de relatórios sem filtro de estado
- WHEN o usuário carrega a página
- THEN todas as igrejas disponíveis são exibidas no seletor
