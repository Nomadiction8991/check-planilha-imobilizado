# Especificação de Dependências por Escopo

## ADDED Requirements

### Requirement: Filtrar dependências por escopo administrativo
O serviço de consulta e navegação de dependências SHALL filtrar registros de dependências e opções de igrejas/administrações de acordo com as administrações permitidas da sessão do usuário autenticado.

#### Scenario: Usuário com escopo restrito acessa lista de dependências
- GIVEN uma sessão com administrações permitidas específicas
- WHEN o usuário consulta as dependências paginadas ou contagem total
- THEN apenas as dependências vinculadas às igrejas das administrações permitidas SHALL ser retornadas

#### Scenario: Administrador acessa lista de dependências
- GIVEN uma sessão com perfil administrador (`is_admin = true`)
- WHEN o usuário consulta as dependências ou opções
- THEN todas as dependências e opções de todas as administrações SHALL ser retornadas
