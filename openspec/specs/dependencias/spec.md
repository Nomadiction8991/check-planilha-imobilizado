# dependencias Specification

## Purpose
TBD - created by archiving change padronizar-passagem-estados-dependencias. Update Purpose after archive.
## Requirements
### Requirement: Disponibilização de Estados no Formulário de Criação de Dependência
O controlador `LegacyDepartmentController@create` SHALL fornecer a lista de estados da federação brasileira (`states`) para a view `departments.create`.

#### Scenario: Acesso ao formulário de criação de dependência
- GIVEN um usuário autenticado com acesso à criação de dependências
- WHEN o usuário acessa a rota `migration.departments.create`
- THEN a view `departments.create` é renderizada com a variável `states` contendo o array de estados configurados no sistema.

### Requirement: Disponibilização de Estados no Formulário de Edição de Dependência
O controlador `LegacyDepartmentController@edit` SHALL fornecer a lista de estados da federação brasileira (`states`) para a view `departments.edit`.

#### Scenario: Acesso ao formulário de edição de dependência
- GIVEN um usuário autenticado com acesso à edição de dependências
- WHEN o usuário acessa a rota `migration.departments.edit` para uma dependência válida
- THEN a view `departments.edit` é renderizada com a variável `states` contendo o array de estados configurados no sistema.

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

