## ADDED Requirements

### Requirement: Opções de relatórios respeitam o escopo administrativo

O sistema SHALL limitar as opções de administração e igreja exibidas na tela de relatórios às administrações permitidas do usuário autenticado. Administradores globais SHALL continuar vendo todas as opções. Um filtro de administração fora do escopo SHALL resultar em nenhuma igreja selecionável.

#### Scenario: Usuário restrito vê somente administrações permitidas

- **GIVEN** um usuário não administrador com uma administração principal e outras administrações permitidas
- **WHEN** ele abre os filtros da tela de relatórios
- **THEN** o seletor de administração contém somente as administrações do seu escopo
- **AND** o seletor de igreja contém somente igrejas vinculadas a essas administrações

#### Scenario: Filtro fora do escopo não revela igrejas

- **GIVEN** um usuário restrito sem acesso à administração 30
- **WHEN** ele solicita a tela de relatórios com `administracao_id=30`
- **THEN** nenhuma igreja da administração 30 é disponibilizada
- **AND** nenhuma seleção de igreja incompatível é mantida

#### Scenario: Administrador mantém opções globais

- **GIVEN** um administrador global autenticado
- **WHEN** ele abre os filtros de relatórios sem administração selecionada
- **THEN** igrejas de todas as administrações continuam disponíveis

### Requirement: Seleção de igreja não contorna o escopo por URL

A tela de relatórios SHALL considerar válida uma igreja somente quando ela pertencer ao conjunto de opções permitido pelo escopo administrativo e pelos filtros atuais. Uma igreja informada diretamente na URL, mas fora desse conjunto, SHALL ser descartada antes de carregar relatórios.

#### Scenario: Igreja fora do escopo é descartada

- **GIVEN** um usuário restrito informa na URL uma igreja vinculada a uma administração não permitida
- **WHEN** a tela de relatórios é carregada
- **THEN** a igreja não fica selecionada
- **AND** nenhum relatório é carregado para ela

#### Scenario: Igreja permitida permanece selecionável

- **GIVEN** uma igreja vinculada a uma administração permitida e compatível com os filtros atuais
- **WHEN** o usuário acessa a tela de relatórios com essa igreja
- **THEN** a igreja permanece selecionada e os relatórios disponíveis são carregados normalmente