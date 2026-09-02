# Delta Specs: Filtro por Estado na Listagem de Usuários

## ADDED Requirements

### Requirement: Filtro de Usuários por Estado (UF)
O sistema SHALL permitir que o usuário filtre a listagem de usuários por Unidade Federativa (`endereco_estado`), preservando a paginação e os demais filtros de busca, status e administração.

#### Scenario: Filtragem por estado específico
- GIVEN usuários cadastrados no sistema com diferentes unidades federativas (`endereco_estado`)
- WHEN o usuário requisita a listagem enviando o parâmetro `estado=SP`
- THEN o sistema SHALL retornar apenas os usuários cujo `endereco_estado` seja igual a "SP"

#### Scenario: Sanitização do parâmetro de estado
- GIVEN uma requisição GET com parâmetro `estado=" rj "`
- WHEN o DTO `UserFilters` for construído a partir da requisição
- THEN a propriedade `state` SHALL ser convertida para "RJ" em maiúsculo e sem espaços

#### Scenario: Integração do filtro na interface de usuários
- GIVEN a visualização da tela de listagem de usuários
- WHEN a tela for renderizada
- THEN o seletor de estado (UF) SHALL exibir as opções de estados disponíveis e manter a seleção atual se informada
