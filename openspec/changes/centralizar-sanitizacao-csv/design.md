# Design Técnico: Centralização de Sanitização de CSV

## Arquitetura e Estrutura
- Criar a classe final `App\Support\LegacyCsvSanitizer`.
- Métodos estáticos:
  - `public static function sanitizeText(?string $value): string`: neutraliza prefixos `=+-@\t\r` com `'`.
  - `public static function sanitizeRow(array $row): array`: aplica `sanitizeText` recursivamente ou iterativamente em cada elemento string da linha.
- Refatorar os consumidores:
  - `App\Services\LegacySpreadsheetImportService`: substituir o método privado por chamada estática a `LegacyCsvSanitizer::sanitizeText`.
  - `App\Services\LegacyReportService`: substituir o método privado por `LegacyCsvSanitizer::sanitizeText`.
  - `App\Services\LegacyAuditTrailService`: substituir o método privado por `LegacyCsvSanitizer::sanitizeText`.

## Testes e Segurança
- `Tests\Unit\Support\LegacyCsvSanitizerTest`: validação unitária direta de todos os caracteres vulneráveis e tipos de entrada.
- Preservação dos testes existentes em `Tests\Unit\Services\LegacySpreadsheetImportServiceTest`, `Tests\Unit\Services\LegacyReportFormularioCsvTest` e `Tests\Unit\Services\LegacyAuditTrailServiceTest`.
