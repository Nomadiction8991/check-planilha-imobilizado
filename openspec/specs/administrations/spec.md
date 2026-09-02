# administrations Specification

## Purpose
TBD - created by archiving change filtro-administracoes-estado. Update Purpose after archive.
## Requirements
### Requirement: Filtrar Administrações por Estado
The system SHALL allow filtering registered administrations by Brazilian state (UF).

#### Scenario: Filtragem por UF válida
- GIVEN administrações cadastradas em diferentes estados (ex.: "SP", "PR", "RJ")
- WHEN o usuário submete a busca filtrando pelo estado "PR"
- THEN apenas administrações com o campo `estado` igual a "PR" devem ser retornadas na paginação

#### Scenario: Preservação do filtro na paginação
- GIVEN uma busca filtrada por estado "SP"
- WHEN os links de paginação ou query string forem gerados pelo DTO
- THEN o parâmetro `estado` deve ser mantido no link com o valor selecionado

#### Scenario: Estado não informado ou vazio
- GIVEN o usuário acessa a listagem de administrações sem especificar estado
- WHEN a consulta for executada
- THEN o sistema retorna administrações de todos os estados sem restrição por UF

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

