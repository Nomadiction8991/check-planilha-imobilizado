# importacao-imobilizado Specification

## Purpose
TBD - created by archiving change melhorar-importacao-planilhas. Update Purpose after archive.
## Requirements
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

### Requirement: Aviso de escopo de importação na tela inicial
A tela de importação SHALL exibir um aviso proeminente em vermelho alertando que a importação processa a igreja inteira, incluindo todas as dependências, não apenas um setor ou dependência selecionada.

#### Scenario: Usuário acessa a tela de importação
- GIVEN a user with access to spreadsheet imports
- WHEN the user opens the spreadsheet import page
- THEN the system displays a red banner stating that the entire church and all dependencies will be processed
- AND the performance note remains visible as secondary guidance

### Requirement: Aviso de escopo de importação na prévia
A prévia da importação SHALL exibir o mesmo aviso de escopo antes da seção de igrejas detectadas.

#### Scenario: Usuário visualiza a prévia com igrejas detectadas
- GIVEN an import preview with detected churches
- WHEN the user opens the preview
- THEN the system displays a red scope banner before church or dependency actions
- AND the banner states that confirming imports all sectors of selected churches
