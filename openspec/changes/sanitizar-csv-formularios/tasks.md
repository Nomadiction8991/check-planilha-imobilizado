## 1. Testes de regressão

- [x] 1.1 Adicionar teste unitário que verifica sanitização em export de formulários (descrição/dependência com `=`, `+`, `-`, `@`, `\t`, `\r`) e preservação de valores sistêmicos.
- [x] 1.2 Garantir que export de backup/posição também sanitiza campos textuais.

## 2. Implementação

- [x] 2.1 Criar método `sanitizeCsvText` em `LegacyReportService` e aplicar a campos textuais de `downloadFormularioCsv` (14.1/14.6), `downloadVerificationBackupCsv` e `downloadPositionCsv`.

## 3. Validação

- [x] 3.1 Executar `php -l` nos arquivos alterados + `php artisan test` direcionado.
- [x] 3.2 Validar `openspec validate --change sanitizar-csv-formularios --json` e saúde (`curl /login`).
- [x] 3.3 Commitar e enviar para deploy automático.
