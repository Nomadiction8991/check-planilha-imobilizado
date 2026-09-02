## ADDED Requirements

### Requirement: Filtrar tipos de bem por UF da administração
The system SHALL filter asset types listing by administration state (UF).

#### Scenario: Filtragem com estado válido
- GIVEN que existem tipos de bem cadastrados para administrações de diferentes estados (ex: SP e RJ)
- WHEN o usuário submete o filtro com `estado=SP`
- THEN a listagem SHALL exibir apenas os tipos de bem cuja administração possui o estado SP.

#### Scenario: Parâmetro de estado vazio ou não informado
- GIVEN a consulta à listagem de tipos de bem
- WHEN o parâmetro `estado` não for informado ou estiver vazio
- THEN a listagem SHALL retornar os tipos de bem sem restrição de estado.
