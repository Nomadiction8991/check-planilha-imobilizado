# Design Técnico: Filtro por Estado em Tipos de Bem

## Arquitetura
1. **DTO `AssetTypeFilters`**:
   - Adicionar `public ?string $state` no construtor.
   - Tratar no `fromRequest` capturando `estado`, convertendo para maiúsculo de até 2 caracteres.
   - Atualizar `toQuery()` para serializar `estado`.

2. **Service `LegacyAssetTypeBrowserService`**:
   - No método `paginate(AssetTypeFilters $filters)`, adicionar cláusula condicional `when($filters->state !== null && $filters->state !== '', fn($query) => $query->whereHas('administracao', fn($adminQuery) => $adminQuery->where('estado', $filters->state)))`.

3. **Controller & View**:
   - Injetar `'states' => (array) config('brazil.states', [])` no retorno da view em `LegacyAssetTypeController@index`.
   - Adicionar o elemento `<select name="estado" id="asset-types-estado-select">` no formulário de filtros de `resources/views/asset-types/index.blade.php`.
