# Memória de Análises - Check Planilha Imobilizado

## Resumo Acumulativo
- Total de análises: 4
- Problemas catalogados: 4
- Padrões recorrentes identificados: 1

## Análise 2026-04-17 - Permissões, autenticação e importação

**Arquivos analisados**:
- `app/Services/LegacyPermissionService.php`
- `app/Services/LegacyAuthSessionService.php`
- `app/Services/LegacyNativeSessionBridge.php`
- `app/Services/LegacySpreadsheetImportService.php`
- `resources/views/administrations/index.blade.php`
- `resources/views/asset-types/index.blade.php`
- `resources/views/churches/index.blade.php`
- `resources/views/departments/index.blade.php`
- `resources/views/products/index.blade.php`
- `resources/views/users/index.blade.php`
- `tests/Feature/LegacyAdministrationManagementTest.php`
- `tests/Feature/LegacySpreadsheetImportTest.php`
- `tests/Feature/LegacyUserManagementTest.php`

**Escopo**: Lógica, Segurança, Performance
**Problemas encontrados**: 1
**Críticos**: 0 | Altos: 1 | Médios: 0 | Baixos: 0

**Padrões identificados**:
- Telas que dependem de admin precisam aceitar o estado vindo do composer e também da sessão legada.
- Fluxos de importação precisam manter o escopo de administração coerente do início ao fim.

**Próximos passos**:
- Continuar validando reconhecimento de admin em telas compartilhadas por layout.
- Manter a importação e os cadastros de usuário alinhados ao escopo de administração.

## Análise 2026-04-17 - Rechecagem pós-correção

**Arquivos analisados**:
- `app/Services/LegacyPermissionService.php`
- `resources/views/administrations/index.blade.php`
- `resources/views/asset-types/index.blade.php`
- `resources/views/churches/index.blade.php`
- `resources/views/departments/index.blade.php`
- `resources/views/products/index.blade.php`
- `resources/views/users/index.blade.php`
- `tests/Feature/LegacyAdministrationManagementTest.php`

**Escopo**: Lógica
**Problemas encontrados**: 0
**Críticos**: 0 | Altos: 0 | Médios: 0 | Baixos: 0

**Padrões identificados**:
- O reconhecimento explícito de admin na sessão evitou perda de ações no layout compartilhado.

**Próximos passos**:
- Manter a mesma checagem explícita para novas telas que compartilhem o layout `migration`.

## Análise 2026-04-17 - Importação sem igreja base

**Arquivos analisados**:
- `app/DTO/SpreadsheetImportUploadData.php`
- `app/Http/Requests/StoreSpreadsheetImportRequest.php`
- `app/Services/LegacySpreadsheetImportService.php`
- `app/Repositories/ImportacaoRepository.php`
- `tests/Feature/LegacySpreadsheetImportTest.php`

**Escopo**: Lógica
**Problemas encontrados**: 1
**Críticos**: 0 | Altos: 1 | Médios: 0 | Baixos: 0

**Padrões identificados**:
- Imports multi-igreja precisam persistir `comum_id` como `NULL` quando não existe igreja base.

**Próximos passos**:
- Manter o contrato da importação alinhado ao schema `importacoes.comum_id`.

## Análise 2026-04-17 - Troca de igreja e logout público

**Arquivos analisados**:
- `app/Services/LegacyAuthSessionService.php`
- `app/Http/Controllers/LegacyRouteCompatibilityController.php`
- `app/Http/Controllers/PublicAccessController.php`
- `routes/web.php`

**Escopo**: Segurança, Lógica
**Problemas encontrados**: 2
**Críticos**: 0 | Altos: 1 | Médios: 1 | Baixos: 0

**Padrões identificados**:
- `comum_id` continua sendo um ponto central de escopo e autorização.
- Ações com efeito colateral ainda aparecem expostas por rotas `GET`.

**Próximos passos**:
- Validar o escopo permitido antes de trocar a igreja ativa.
- Converter logout público para `POST` com proteção CSRF.
