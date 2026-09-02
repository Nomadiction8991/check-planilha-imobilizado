# Design: Filtro por Estado (UF) em Produtos

## Arquitetura e Decisões Técnicas

1. **DTO `ProductFilters`**:
   - Adicionar campo `public ?string $state`.
   - Em `fromRequest(Request $request)`, extrair `estado`, converter para maiúsculo (2 letras) ou `null` se vazio.
   - Em `toQuery()`, adicionar `'estado' => $this->state` se não for nulo/vazio.

2. **Serviço `LegacyProductBrowserService`**:
   - Adicionar condição `->when($filters->state !== null && $filters->state !== '', fn ($query) => $query->whereHas('comum', fn ($churchQuery) => $churchQuery->where('estado', $filters->state)))`.

3. **Controlador `LegacyProductController`**:
   - Injetar `'states' => (array) config('brazil.states', [])` nos métodos `index` e `verification`.

4. **Views Blade**:
   - Em `resources/views/products/index.blade.php` e `resources/views/products/verification.blade.php`: adicionar o `<select name="estado">` no bloco de filtros principais, iterando sobre `$states`.
