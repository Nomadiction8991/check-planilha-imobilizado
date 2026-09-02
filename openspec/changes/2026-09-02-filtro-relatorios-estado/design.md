# Design: Filtro por Estado na Seleção de Relatórios

## Arquitetura
1. **Interface de Contrato**:
   Atualizar `LegacyReportServiceInterface::churchOptions(?int $administrationId = null, ?string $state = null): Collection` adicionando o argumento opcional `$state`.
2. **Serviço de Relatórios**:
   Em `LegacyReportService::churchOptions`, filtrar `Comum` por `estado` quando `$state` for informado e não vazio.
3. **Controller**:
   Em `LegacyReportController::index`, ler o parâmetro `estado` do request (sanitizado), repassá-lo ao `churchOptions($administrationId, $state)`, e enviar `states` e `selectedState` para a view `reports.index`.
4. **View**:
   Adicionar no formulário de filtros de `reports/index.blade.php` o select de `Estado (UF)` com opções vindas de `states` / `config('brazil.states')`.
