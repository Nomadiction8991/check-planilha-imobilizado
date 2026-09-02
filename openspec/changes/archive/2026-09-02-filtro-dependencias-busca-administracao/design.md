# Design: Filtro por Administração em Dependências

## Context & Architecture
A entidade `Dependencia` possui relacionamento direto `comum: BelongsTo(Comum::class, 'comum_id')`, e `Comum` possui `administracao: BelongsTo(Administracao::class, 'administracao_id')`.
Para filtrar dependências por administração, filtramos através do relacionamento `comum` (`whereHas('comum', fn ($query) => $query->where('administracao_id', $filters->administrationId))`).

## Component Decisions

1. **`DepartmentFilters`**:
   - Adicionar `public ?int $administrationId`.
   - Em `fromRequest`: ler `(int) $request->query('administracao_id', 0)`.
   - Em `toQuery`: incluir `administracao_id` quando não nulo.

2. **`LegacyDepartmentBrowserServiceInterface` & `LegacyDepartmentBrowserService`**:
   - Adicionar método `public function administrationOptions(): Collection`.
   - No `paginate`, aplicar `->when($filters->administrationId !== null, fn ($query) => $query->whereHas('comum', fn ($q) => $q->where('administracao_id', $filters->administrationId)))`.

3. **`LegacyDepartmentController::index`**:
   - Injetar `'administrations' => $this->departments->administrationOptions()` na view `departments.index`.

4. **Blade View `departments.index`**:
   - Adicionar campo de busca de administração com `data-departments-admin-search`, select com `data-departments-admin-select`, status acessível com `data-departments-admin-status` e script de busca client-side.

5. **Testes**:
   - `DepartmentFiltersTest`: testes unitários para parsing e serialização.
   - `LegacyDepartmentBrowserServiceTest`: teste unitário para consulta com filtro de administração e listagem de opções de administração.
   - `LegacyDepartmentControllerTest`: testes de feature para index com filtro `administracao_id` e presença dos elementos no DOM.
