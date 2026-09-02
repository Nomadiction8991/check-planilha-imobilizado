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

