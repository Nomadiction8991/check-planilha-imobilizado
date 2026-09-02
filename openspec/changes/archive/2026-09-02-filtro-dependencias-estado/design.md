# Design: Filtro por Estado em Dependências

## Abordagem Técnica
1. **DTO `DepartmentFilters`**:
   - Adicionar propriedade `public ?string $state` no construtor.
   - No método `fromRequest`, ler `estado`, fazer `trim`, `mb_substr(..., 0, 2)` e `mb_strtoupper(..., 'UTF-8')`.
   - No método `toQuery`, incluir `estado` se preenchido.

2. **Serviço `LegacyDepartmentBrowserService`**:
   - No método `paginate()`, adicionar condição `when($filters->state !== null && $filters->state !== '', ...)` usando `whereHas('comum', fn ($query) => $query->where('estado', $filters->state))`.

3. **Controller `LegacyDepartmentController`**:
   - No método `index()`, injetar `'states' => (array) config('brazil.states', [])` no array de dados enviado à view.

4. **View `resources/views/departments/index.blade.php`**:
   - Adicionar o `<label class="filters-principal">` com `<select name="estado" id="departments-estado-select">` permitindo escolher uma UF ou todas.

5. **Testes**:
   - `DepartmentFiltersTest`: testar leitura e sanitização de `estado`.
   - `LegacyDepartmentBrowserServiceTest`: testar paginação filtrando por `state`.
   - `LegacyDepartmentControllerTest`: testar renderização do select de estados e passagem de filtro.
