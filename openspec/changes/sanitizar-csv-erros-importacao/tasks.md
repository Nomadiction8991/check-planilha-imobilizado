# Tasks: Sanitização de Fórmulas no CSV de Erros de Importação

- [x] 1. Criar teste unitário em `LegacySpreadsheetImportServiceTest` cobrindo a sanitização no gerador de linhas de erros de importação
- [x] 2. Implementar `sanitizeCsvText` em `LegacySpreadsheetImportService` e aplicar em `downloadImportErrorsCsv`
- [x] 3. Executar `php -l` nos arquivos alterados
- [x] 4. Rodar testes da suíte (`php artisan test`) e garantir 100% verde
- [x] 5. Validar OpenSpec (`openspec validate --change sanitizar-csv-erros-importacao --json`)
