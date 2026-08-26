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

### Requirement: Prévia exibe lista de linhas com erro
O sistema SHALL apresentar, na tela de prévia da importação, a lista das linhas que falharam na análise com sua posição no CSV, código, nome e motivo da falha.

#### Scenario: Análise contém linhas com falha
- GIVEN an import analysis containing rows with error status
- WHEN the user opens the import preview
- THEN the system SHOULD display an errors panel listing each failed row with its CSV line number, code, name and reason

#### Scenario: Lista de erros limitada para não estourar a página
- GIVEN an analysis containing more failing rows than the display limit
- WHEN the preview is rendered
- THEN the system SHOULD show at most the configured maximum number of error rows

#### Scenario: Análise sem falhas não exibe o painel
- GIVEN an import analysis without any failed row
- WHEN the user opens the import preview
- THEN the system SHOULD NOT render the error list panel
