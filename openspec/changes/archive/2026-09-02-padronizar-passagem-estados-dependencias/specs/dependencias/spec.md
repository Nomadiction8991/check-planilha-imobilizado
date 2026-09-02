# Especificação de Dependências: Padronização de Estados nas Views

## ADDED Requirements

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
