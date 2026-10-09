# Design Técnico: Diagnóstico de Planilhas

## Arquitetura
1. **`App\DTO\SpreadsheetDiagnosticReport`**:
   - DTO imutável contendo contagem de linhas, formato detectado, mapeamento de colunas, contagem de bens patrimoniais válidos, igrejas/comuns identificadas, alertas e erros.
2. **`App\Services\SpreadsheetDiagnosticService`**:
   - Utiliza `UniversalSpreadsheetReader` para ler linhas em memória.
   - Detecta automaticamente o formato (CSV delimitado, XLS BIFF8 ou XLSX OpenXML).
   - Localiza a linha do cabeçalho real (`Código` e `Nome`).
   - Mapeia os índices de `Código`, `Nome`, `Localidade` e `Dependência`.
   - Analisa a integridade de cada registro de bem.
3. **`App\Console\Commands\SpreadsheetDiagnoseCommand`**:
   - Comando CLI formatado com tabelas de console ou saída `--json`.
