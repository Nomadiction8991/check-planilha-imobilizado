# Proposta: Centralizar Sanitização de CSV Contra Injeção de Fórmulas

## Motivação
Três serviços (`LegacySpreadsheetImportService`, `LegacyReportService` e `LegacyAuditTrailService`) implementavam métodos privados idênticos `sanitizeCsvText` para prevenir injeção de fórmulas CSV (CWE-1236). Centralizar essa lógica na camada `App\Support\LegacyCsvSanitizer` elimina a duplicação, garante uma regra canônica e consistente de neutralização para strings, números e arrays/linhas completas de exportação, além de facilitar a cobertura unitária exaustiva.

## Escopo
- Criar a classe utilitária canônica `App\Support\LegacyCsvSanitizer`.
- Refatorar `LegacySpreadsheetImportService`, `LegacyReportService` e `LegacyAuditTrailService` para utilizar o novo suporte.
- Cobrir a classe utilitária com testes unitários dedicados (strings, nulos, prefixos perigosos `=+-@\t\r`, neutralização de linhas completas).
- Atualizar e manter verdes todos os testes unitários e de feature existentes.
