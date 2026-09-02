## MODIFIED Requirements

### Requirement: Disponibilização de Lista de Estados nas Telas de Administração
O sistema DEVE disponibilizar a lista de estados da federação (`states`) nas views de listagem (`index`), criação (`create`) e edição (`edit`) de administrações. O formulário de criação DEVE preservar a cidade informada anteriormente quando for reexibido após falha de validação.

#### Scenario: Visualização da listagem de administrações com estados
- **GIVEN** um usuário autenticado acessando a listagem de administrações
- **WHEN** a tela for renderizada
- **THEN** a view deve receber a lista de estados para exibição no filtro por UF.

#### Scenario: Telas de cadastro e edição de administração com estados
- **GIVEN** um usuário autenticado acessando a criação ou edição de administração
- **WHEN** a tela for renderizada
- **THEN** a view deve receber a lista de estados para preenchimento do select de UF.

#### Scenario: Criação reexibida preserva a cidade informada
- **GIVEN** a pessoa informou uma cidade e o formulário de criação retornou com dados antigos
- **WHEN** a tela de criação for renderizada novamente
- **THEN** o seletor de cidade deve carregar a cidade informada como opção selecionada depois do carregamento das localidades.
