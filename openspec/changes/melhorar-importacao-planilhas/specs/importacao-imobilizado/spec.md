## ADDED Requirements

### Requirement: Importação com validação robusta
O sistema SHALL validar cada linha da planilha e reportar erros específicos.

#### Scenario: Planilha com formato inválido
- GIVEN a user uploading a file without required columns
- WHEN the user clicks import
- THEN the system SHOULD return a clear error message identifying missing columns

#### Scenario: Planilha com dados inconsistentes
- GIVEN a user uploading a file with invalid data types
- WHEN the user clicks import
- THEN the system SHOULD return a clear error message identifying inconsistent rows
