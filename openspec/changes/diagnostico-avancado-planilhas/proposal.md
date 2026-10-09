# Proposta: Diagnóstico Avançado e Pré-validação de Planilhas

## Motivação e Contexto
O processo de importação de bens patrimoniais pode falhar se o arquivo enviado estiver corrompido, em formato incorreto ou com colunas deslocadas (por exemplo, cabeçalhos na linha 25 em vez da linha 1). 
Para evitar falhas na importação definitiva e dar autonomia à equipe de TI e conferentes, disponibilizamos um serviço de diagnóstico prévio (`SpreadsheetDiagnosticService`) e um comando artisan (`spreadsheet:diagnose`).

## Objetivos
1. Criar `App\DTO\SpreadsheetDiagnosticReport` com tipagem estrita (PHP 8.3).
2. Criar `App\Services\SpreadsheetDiagnosticService` para inspecionar arquivos CSV, XLS e XLSX via `UniversalSpreadsheetReader`.
3. Criar o comando artisan `php artisan spreadsheet:diagnose {path} {--json}` para verificação imediata via terminal.
4. Cobertura com testes unitários e de feature.
