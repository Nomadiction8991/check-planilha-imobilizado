# Tarefas de Implementação

- [x] Atualizar testes unitários em `AssetTypeFiltersTest` para cobrir o campo `state` e sanitização de UF
- [x] Atualizar testes unitários em `LegacyAssetTypeBrowserServiceTest` com cenário de filtro por estado
- [x] Atualizar `AssetTypeFilters.php` com o novo campo `state`
- [x] Atualizar `LegacyAssetTypeBrowserService.php` aplicando a cláusula `whereHas('administracao')`
- [x] Atualizar `LegacyAssetTypeController.php` passando os estados para a view
- [x] Atualizar `resources/views/asset-types/index.blade.php` com o select de UF
- [x] Atualizar e rodar testes de controller/feature (`LegacyAssetTypeControllerTest` e `LegacyAssetTypeManagementTest`)
- [x] Validar OpenSpec e rodar suíte de testes completa
