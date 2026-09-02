# Tarefas de Implementação

- [x] Atualizar testes unitários de `ProductFiltersTest` para cobrir extração e serialização do campo `state`
- [x] Atualizar DTO `ProductFilters` com campo `$state`
- [x] Atualizar testes unitários de `LegacyProductBrowserServiceTest` com filtragem por estado
- [x] Atualizar `LegacyProductBrowserService` com filtro `whereHas('comum', ...)` por estado
- [x] Atualizar `LegacyProductController` para passar `states` para as views de listagem e verificação
- [x] Atualizar views `products/index.blade.php` e `products/verification.blade.php` com o select de Estado (UF)
- [x] Atualizar testes de Feature `LegacyProductControllerTest` cobrindo o filtro de estado
- [x] Validar suíte de testes e sintaxe dos arquivos alterados
