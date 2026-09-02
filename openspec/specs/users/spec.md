# users Specification

## Purpose
TBD - created by archiving change filtro-usuarios-estado. Update Purpose after archive.
## Requirements
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

### Requirement: Formulário de usuário preserva a UF do endereço
O sistema SHALL renderizar o campo de UF do endereço usando o código da UF como valor enviado e o nome da UF como texto visível, selecionando o valor atual do cadastro ou o valor antigo informado quando o formulário for reexibido.

#### Scenario: Editar usuário com UF cadastrada
- **GIVEN** um usuário cujo endereço possui a UF `SP`
- **WHEN** a pessoa abre o formulário de edição
- **THEN** a opção `SP` deve ser selecionada
- **AND** o texto visível deve identificar São Paulo

#### Scenario: Reexibir formulário após erro de validação
- **GIVEN** a pessoa informou a UF `MT` e o formulário retornou com dados antigos
- **WHEN** o formulário é renderizado novamente
- **THEN** a opção `MT` deve permanecer selecionada
- **AND** o campo deve continuar enviando `MT` como valor