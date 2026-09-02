# Design Técnico: Filtro por Administração em Tipos de Bem

## Arquitetura e Decisões

1. **DTO `AssetTypeFilters`**:
   - Adicionar propriedade `public ?int $administrationId`.
   - Atualizar `fromRequest` para extrair `administracao_id` query param.
   - Atualizar `toQuery` para incluir `administracao_id` quando não nulo.

2. **Interface e Serviço `LegacyAssetTypeBrowserService`**:
   - Adicionar método `administrationOptions(): Collection` na interface e implementação para fornecer a lista de administrações autorizadas/disponíveis ordenadas por ID/descrição.
   - No método `paginate()`, adicionar cláusula `when($filters->administrationId !== null)` aplicando `where('administracao_id', $filters->administrationId)`.

3. **Controller `LegacyAssetTypeController`**:
   - No método `index(Request $request)`, injetar `administrations` obtidas via `administrationOptions()`.

4. **View `resources/views/asset-types/index.blade.php`**:
   - Incluir campo de busca digitável `data-asset-types-admin-search` e `<select name="administracao_id" data-asset-types-admin-select>`.
   - Adicionar script vanilla JS com progressive enhancement para filtragem em tempo real das opções com feedback acessível (`aria-live="polite"`).

5. **Testes**:
   - Unit: `AssetTypeFiltersTest`, `LegacyAssetTypeBrowserServiceTest`.
   - Feature: `LegacyAssetTypeControllerTest`, `LegacyAssetTypeManagementTest`.
