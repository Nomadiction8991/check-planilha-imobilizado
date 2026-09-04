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

### Requirement: Filtrar tipos de bem por UF da administração
The system SHALL filter asset types listing by administration state (UF).

#### Scenario: Filtragem com estado válido
- GIVEN que existem tipos de bem cadastrados para administrações de diferentes estados (ex: SP e RJ)
- WHEN o usuário submete o filtro com `estado=SP`
- THEN a listagem SHALL exibir apenas os tipos de bem cuja administração possui o estado SP.

#### Scenario: Parâmetro de estado vazio ou não informado
- GIVEN a consulta à listagem de tipos de bem
- WHEN o parâmetro `estado` não for informado ou estiver vazio
- THEN a listagem SHALL retornar os tipos de bem sem restrição de estado.

### Requirement: Atualização automática dos filtros de tipos de bem
A tela de tipos de bem SHALL enviar automaticamente uma nova consulta quando um filtro server-side for alterado, SHALL aguardar uma breve pausa durante a digitação da busca textual e SHALL manter as buscas auxiliares e o envio manual disponíveis.

#### Scenario: Alteração de filtro atualiza a listagem
- **GIVEN** a pessoa está na listagem de tipos de bem
- **WHEN** ela altera a administração, o estado ou a busca geral
- **THEN** a tela envia automaticamente a consulta com os valores atuais
- **AND** reinicia a consulta na primeira página

#### Scenario: Busca auxiliar de administração permanece local
- **GIVEN** a tela oferece uma busca auxiliar para localizar administrações
- **WHEN** a pessoa digita nessa busca
- **THEN** somente as opções do select são filtradas no navegador
- **AND** a busca auxiliar não é incluída na consulta

#### Scenario: Limpeza da busca geral atualiza os resultados
- **GIVEN** a busca geral contém um valor
- **WHEN** a pessoa usa o controle nativo para limpar o campo
- **THEN** a tela envia automaticamente a consulta sem a busca

#### Scenario: Filtro manual continua disponível
- **GIVEN** a pessoa deseja confirmar os filtros pelo botão
- **WHEN** ela aciona “Filtrar”
- **THEN** a consulta é enviada normalmente sem duplicidade automática

