## MODIFIED Requirements

### Requirement: Filtro por administração na listagem de igrejas

O sistema SHALL permitir filtrar a listagem de igrejas por administração selecionada (`administracao_id`), além da busca por texto, e SHALL limitar a consulta de usuários restritos às igrejas cujas administrações pertençam ao escopo autorizado. Um filtro por administração fora do escopo SHALL retornar uma lista vazia.

#### Scenario: Filtragem com administração selecionada
- GIVEN que existem igrejas pertencentes a diferentes administrações
- WHEN o usuário submete a listagem de igrejas com o parâmetro `administracao_id`
- THEN apenas as igrejas vinculadas à administração informada DEVEM ser retornadas

#### Scenario: Usuário restrito consulta administrações permitidas
- GIVEN um usuário restrito com uma administração principal e administrações adicionais autorizadas
- WHEN ele abre a listagem de igrejas sem filtro de administração
- THEN somente igrejas vinculadas às administrações autorizadas DEVEM ser retornadas

#### Scenario: Filtro fora do escopo não revela igrejas
- GIVEN um usuário restrito sem acesso à administração solicitada
- WHEN ele consulta a listagem com essa administração
- THEN nenhuma igreja dessa administração DEVE ser retornada

#### Scenario: Administrador mantém consulta global
- GIVEN um administrador global autenticado e igrejas em múltiplas administrações
- WHEN ele abre a listagem sem filtro
- THEN igrejas de todas as administrações DEVEM permanecer disponíveis

#### Scenario: Busca em tempo real de administrações no select
- GIVEN a presença do select de administrações na listagem de igrejas
- WHEN o usuário digita no campo de busca de administração
- THEN as opções do select DEVEM ser filtradas instantaneamente sem recarregar a página
