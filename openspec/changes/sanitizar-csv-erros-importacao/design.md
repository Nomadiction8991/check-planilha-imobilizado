# Design: Sanitização de Fórmulas no CSV de Erros de Importação

## Context

O serviço `LegacySpreadsheetImportService` produz um gerador (`yield`) de linhas de 16 colunas para download de CSV de correção de erros. Os valores textuais dos erros (`descricao_csv` / `bem` + `complemento`, `localidade`, `dependencia`) eram adicionados diretamente às posições 3, 10 e 15 sem sanitização.

## Technical Decisions

1. **Método de sanitização**: Implementar `sanitizeCsvText(?string $value): string` privado em `LegacySpreadsheetImportService` idêntico ao já validado em `LegacyReportService` e `LegacyAuditTrailService`:
   ```php
   private function sanitizeCsvText(?string $value): string
   {
       $value ??= '';
       if ($value !== '' && str_contains("=+-@\t\r", $value[0])) {
           return "'" . $value;
       }
       return $value;
   }
   ```
2. **Aplicação nos campos**: Sanitizar `$originalName`, `localidade` e `dependencia` antes de atribuir a `$row[3]`, `$row[10]` e `$row[15]`.
3. **Preservação**: Manter cabeçalhos e índices inalterados.
