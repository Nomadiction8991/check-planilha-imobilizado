## ADDED Requirements

### Requirement: Filtro por administração na consulta e exportação de auditoria
O sistema SHALL permitir filtrar os registros de eventos auditados por uma administração específica na tela de auditoria e na exportação CSV, disponibilizando uma busca assistida para localização rápida de administrações em conformidade com o escopo do usuário autenticado.

#### Scenario: Filtrar auditoria por administração específica
- GIVEN um usuário administrador autenticado na tela de auditoria
- WHEN ele seleciona uma administração específica no filtro de administração
- THEN a listagem e paginação exibem apenas eventos vinculados àquela administração
- AND os filtros aplicados são preservados nos links de paginação e no botão de exportação CSV

#### Scenario: Filtragem assistida de administração na interface
- GIVEN a tela de consulta de auditoria
- WHEN o usuário digita termos no campo de busca rápida de administração
- THEN o select correspondente oculta opções não correspondentes e exibe feedback acessível de contagem ou ausência de resultados
- AND se nenhuma opção casar com o termo pesquisado, informa adequadamente no status acessível
