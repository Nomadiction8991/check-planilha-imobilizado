## ADDED Requirements

### Requirement: Operações de relatório respeitam o escopo administrativo

O sistema SHALL validar o escopo administrativo da igreja antes de gerar uma prévia, posição de estoque, histórico ou exportação de relatório. Usuários restritos SHALL poder consultar somente igrejas vinculadas às suas administrações permitidas, enquanto administradores globais SHALL manter acesso a qualquer igreja existente.

#### Scenario: Prévia direta fora do escopo é rejeitada

- **GIVEN** um usuário restrito sem acesso à administração da igreja informada
- **WHEN** ele solicita diretamente a prévia de um formulário para essa igreja
- **THEN** a operação é rejeitada com uma mensagem controlada de escopo
- **AND** os dados da igreja e do formulário não são carregados

#### Scenario: Exportações e posição fora do escopo são rejeitadas

- **GIVEN** um usuário restrito informa uma igreja fora das administrações permitidas
- **WHEN** ele solicita a posição de estoque ou qualquer CSV de relatório dessa igreja
- **THEN** a operação é rejeitada sem gerar conteúdo para download

#### Scenario: Igreja permitida continua gerando relatório

- **GIVEN** um usuário restrito solicita um relatório para uma igreja de administração permitida
- **WHEN** a operação é processada
- **THEN** a prévia ou exportação segue o fluxo existente normalmente

#### Scenario: Administrador mantém acesso global

- **GIVEN** um administrador global autenticado
- **WHEN** ele solicita relatório para uma igreja de qualquer administração existente
- **THEN** a operação continua disponível sem bloqueio de escopo