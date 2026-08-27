## MODIFIED Requirements

### Requirement: Filtros de período da auditoria
O sistema SHALL aceitar filtros de período somente quando cada data estiver no formato AAAA-MM-DD e representar uma data válida do calendário. Quando ambas forem informadas, a data final SHALL ser igual ou posterior à data inicial. Ao rejeitar o filtro, o sistema SHALL retornar à tela de auditoria preservando os demais filtros informados e SHALL explicar o erro no campo correspondente.

#### Scenario: Data de calendário inexistente
- GIVEN que usuário informa `data_inicio=2026-02-31`
- WHEN usuário consulta auditoria
- THEN sistema rejeita filtro e informa que data inicial precisa usar uma data válida
- AND sistema preserva filtros enviados no redirecionamento

#### Scenario: Data final anterior à inicial
- GIVEN que usuário informa data inicial posterior à data final
- WHEN usuário consulta ou exporta auditoria
- THEN sistema rejeita filtro e informa que data final não pode ser anterior à inicial
- AND sistema preserva filtros enviados no redirecionamento

#### Scenario: Período válido
- GIVEN que usuário informa duas datas válidas em ordem cronológica
- WHEN usuário consulta auditoria
- THEN sistema aplica período informado sem erro
