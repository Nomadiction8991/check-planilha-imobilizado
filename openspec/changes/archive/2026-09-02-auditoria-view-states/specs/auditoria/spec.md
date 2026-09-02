# Delta de Auditoria

## ADDED Requirements

### Requirement: [ADDED] Passagem de Estados para View de Auditoria
O sistema DEVE fornecer a lista de estados federativos brasileiros para a view de listagem de auditoria no `LegacyAuditController`.

#### Scenario: Visualização da página de auditoria com estados
- GIVEN um usuário com permissão de auditoria autenticado
- WHEN o usuário acessa a listagem de auditoria
- THEN a view recebe a coleção/array de estados configurados no sistema
