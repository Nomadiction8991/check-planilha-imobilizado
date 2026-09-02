# asset-types Specification

## Purpose
TBD - created by archiving change filtro-tipos-bens-busca-administracao. Update Purpose after archive.
## Requirements
### Requirement: Filtragem por Administração em Tipos de Bem
O sistema DEVE permitir a filtragem da listagem de tipos de bem pelo identificador de administração fornecido nos parâmetros de busca.

#### Scenario: Usuário filtra listagem por administração específica
- GIVEN um conjunto de tipos de bem cadastrados vinculados a diferentes administrações
- WHEN o usuário envia a requisição GET com o parâmetro `administracao_id` preenchido com um ID válido
- THEN a listagem DEVE retornar apenas os tipos de bem pertencentes àquela administração informada respeitando o escopo do usuário.

### Requirement: Busca Progressiva no Seletor de Administração na View
A interface DEVE fornecer um campo interativo de busca que filtra as opções do select de administração em tempo real sem recarregar a página.

#### Scenario: Usuário digita texto no campo de busca de administração
- GIVEN a visualização da tela de tipos de bem com o seletor de administrações
- WHEN o usuário digita um termo no campo de busca de administração
- THEN as opções não correspondentes DEVEM ser ocultadas e desabilitadas no select, exibindo mensagem de status caso nenhuma seja encontrada.

