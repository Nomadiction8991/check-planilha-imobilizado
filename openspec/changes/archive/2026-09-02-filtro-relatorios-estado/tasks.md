# Tarefas de Implementação

- [x] Atualizar contrato `LegacyReportServiceInterface::churchOptions(?int $administrationId = null, ?string $state = null)` <!-- id: 0 -->
- [x] Implementar filtro por estado em `LegacyReportService::churchOptions` <!-- id: 1 -->
- [x] Atualizar `LegacyReportController::index` para ler `estado` e passar para serviço e view <!-- id: 2 -->
- [x] Atualizar view `resources/views/reports/index.blade.php` com o campo de seleção de estado <!-- id: 3 -->
- [x] Adicionar testes unitários e de feature para o filtro por estado em relatórios <!-- id: 4 -->
