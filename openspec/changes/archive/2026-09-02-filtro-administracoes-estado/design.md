# Design Técnico: Filtro por Estado nas Administrações

## Arquitetura
1. **DTO `AdministrationFilters`**:
   - Adicionar propriedade `public ?string $state = null`.
   - No método `fromRequest(Request $request)`: extrair `trim((string) $request->query('estado', ''))`, validar se não está vazio e normalizar para maiúsculas (máximo 2 caracteres).
   - No método `toQuery()`: incluir `'estado' => $this->state` se não for nulo.

2. **Serviço `LegacyAdministrationBrowserService`**:
   - No método `paginate(AdministrationFilters $filters)`: adicionar cláusula `->when($filters->state !== null && $filters->state !== '', fn ($query) => $query->where('estado', $filters->state))`.

3. **Controller e View (`LegacyAdministrationController` e `views/administrations/index.blade.php`)**:
   - Injetar/disponibilizar a lista de estados `config('brazil.states')` na view ou lê-la diretamente na view.
   - Adicionar o elemento `<label class="filters-principal"> Estado <select name="estado"> ... </select></label>` no formulário de filtros com acessibilidade e compatibilidade visual (seguindo o design system do projeto).

4. **Testes**:
   - `AdministrationFiltersTest`: testar instanciação com/sem estado e retorno em `toQuery()`.
   - `LegacyAdministrationControllerTest` / `LegacyAdministrationManagementTest`: testar filtragem por estado na listagem.
