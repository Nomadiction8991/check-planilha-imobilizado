# Design Técnico: Busca de Administração no Formulário de Importação

## Visão Geral
Adicionar o componente de filtro progressivo no formulário de envio de planilhas (`resources/views/spreadsheets/import.blade.php`).

## Componentes Envolvidos
1. **View Blade (`spreadsheets/import.blade.php`)**:
   - Campo `<input type="search" id="spreadsheets-admin-search" data-spreadsheets-admin-search aria-controls="spreadsheets-admin-select">`.
   - `<select id="spreadsheets-admin-select" name="administracao_id" data-spreadsheets-admin-select>`.
   - `<p id="spreadsheets-admin-search-status" data-spreadsheets-admin-status role="status" aria-live="polite" hidden>`.
   - Script cliente autocontido e isolado via IIFE para filtragem dinâmica de `<option>`.

2. **Testes (`tests/Feature/LegacySpreadsheetImportTest.php`)**:
   - Teste de integração/renderização garantindo que a busca de administração, seletor com atributos de controle e elemento de feedback de acessibilidade estejam presentes na página.
