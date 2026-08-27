## MODIFIED Requirements

### Requirement: Exportação de auditoria respeita filtros e escopo

A exportação SHALL retornar somente eventos compatíveis com filtros e escopo do usuário, mantendo cabeçalho e colunas existentes. Campos textuais controlados por usuários SHALL ser neutralizados contra interpretação como fórmula por planilhas.

#### Scenario: Exportação filtrada protege conteúdo textual

- **WHEN** usuário autorizado exporta auditoria com filtros aplicados e evento contendo texto iniciado por caractere de fórmula
- **THEN** download contém somente eventos compatíveis e prefixa texto perigoso com apóstrofo

#### Scenario: Exportação sem resultados mantém comportamento atual

- **WHEN** filtros não encontram eventos no escopo do usuário
- **THEN** sistema não gera arquivo vazio e informa que não há eventos para os filtros atuais
