# Tarefas de Implementação

- [x] 1. Atualizar `LegacyAuditTrailService` para suportar filtro `administracao_id` em paginação e exportação CSV
- [x] 2. Atualizar `LegacyAuditController` para receber `administracao_id`, repassar ao serviço e enviar lista de administrações à view
- [x] 3. Atualizar view `resources/views/audits/index.blade.php` com campos de busca/seleção de administração e script assistido
- [x] 4. Atualizar/criar testes unitários e de feature para validar o novo filtro
- [x] 5. Validar suite de testes, verificar integridade do código com linter/php -l e validar openspec
