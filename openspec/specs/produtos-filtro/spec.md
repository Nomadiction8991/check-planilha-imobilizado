# produtos-filtro Specification

## Purpose
TBD - created by archiving change filtro-produtos-estado. Update Purpose after archive.
## Requirements
### Requirement: Filtro por Estado (UF) em Produtos
O sistema DEVE permitir filtrar produtos cadastrados pelo estado (UF) da igreja associada via parâmetro `estado`.

#### Scenario: Filtragem de produtos por estado válida
- GIVEN produtos vinculados a igrejas em diferentes estados ("SP", "RJ", "MG")
- WHEN o usuário acessa a listagem de produtos informando `estado=SP`
- THEN apenas produtos de igrejas de São Paulo devem ser retornados
- AND a paginação e links de ordenação devem manter o filtro `estado=SP`

#### Scenario: Filtro de estado vazio ou ausente
- GIVEN produtos cadastrados em diversos estados
- WHEN o usuário acessa a listagem de produtos sem o parâmetro `estado` ou com `estado` vazio
- THEN produtos de todos os estados devem ser retornados normalmente

