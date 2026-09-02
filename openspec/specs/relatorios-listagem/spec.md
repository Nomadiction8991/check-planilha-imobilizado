# relatorios-listagem Specification

## Purpose
TBD - created by archiving change filtro-relatorios-busca-administracao. Update Purpose after archive.
## Requirements
### Requirement: Filtro por administração na seleção de relatórios
O sistema SHALL permitir filtrar a lista de congregações disponíveis na tela de relatórios por administração informada (`administracao_id`), além de fornecer as opções de administração para o formulário.

#### Scenario: Listagem de opções de administrações
- GIVEN que existem administrações cadastradas no banco
- WHEN o usuário acessa a tela de relatórios (`/reports`)
- THEN a view DEVE receber a lista de administrações ordenadas por descrição para permitir o filtro

#### Scenario: Filtragem dinâmica de administrações no select
- GIVEN a presença do select de administrações na tela de relatórios
- WHEN o usuário digita no campo de busca de administração
- THEN as opções do select de administração DEVEM ser filtradas instantaneamente sem recarregar a página

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

