## MODIFIED Requirements

### Requirement: Usuário restrito só consulta produtos das administrações permitidas

O sistema SHALL limitar a consulta e as opções relacionadas a produtos às administrações permitidas para o usuário autenticado. Administradores SHALL manter acesso global. A mesma definição de escopo deve ser usada para proteger as operações de escrita do inventário, sem depender dos filtros enviados pelo navegador.

#### Scenario: Usuário restrito consulta produtos de várias administrações permitidas

- **GIVEN** um usuário restrito com uma administração principal e outras administrações permitidas
- **WHEN** ele abre a listagem de produtos
- **THEN** são exibidos apenas produtos vinculados a igrejas dessas administrações

#### Scenario: Usuário restrito tenta filtrar por administração não permitida

- **GIVEN** um usuário restrito sem acesso a uma administração
- **WHEN** ele informa essa administração no filtro da listagem
- **THEN** nenhum produto dessa administração é exibido

#### Scenario: Administrador consulta produtos sem restrição

- **GIVEN** um usuário administrador autenticado
- **WHEN** ele abre a listagem de produtos
- **THEN** produtos de todas as administrações são elegíveis para consulta

#### Scenario: Usuário restrito consulta igrejas e dependências

- **GIVEN** um usuário restrito com administrações permitidas
- **WHEN** o sistema carrega as opções de igreja e dependência
- **THEN** as opções pertencem somente às administrações permitidas

#### Scenario: Usuário restrito consulta tipos de bem

- **GIVEN** um usuário restrito com uma administração ativa
- **WHEN** o sistema carrega os tipos de bem
- **THEN** são exibidos tipos da administração ativa e tipos compartilhados sem administração
