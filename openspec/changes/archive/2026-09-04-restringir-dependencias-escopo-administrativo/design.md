# Design Técnico - Restrição de Dependências por Escopo

## Abordagem
Adicionar o método auxiliar privado `currentAdministrationScopeIds()` ao `LegacyDepartmentBrowserService`, espelhando o padrão já consolidado em `LegacyChurchBrowserService`, `LegacyProductBrowserService` e `LegacyReportService`.

### Pontos Afetados:
1. `paginate(DepartmentFilters $filters)`:
   Aplicar filtro `whereHas('comum', fn($q) => $q->whereIn('administracao_id', $scopeIds))` quando `$scopeIds !== null`.
2. `churchOptions()`:
   Aplicar filtro `whereIn('administracao_id', $scopeIds)` quando `$scopeIds !== null`.
3. `administrationOptions()`:
   Aplicar filtro `whereIn('id', $scopeIds)` quando `$scopeIds !== null`.
4. `countAll()`:
   Aplicar filtro `whereHas('comum', fn($q) => $q->whereIn('administracao_id', $scopeIds))` quando `$scopeIds !== null`.
