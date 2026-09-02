# Design: Filtro por Administração em Produtos

## Context & Architecture
A entidade `Produto` possui relacionamento com `comum: BelongsTo(Comum::class, 'comum_id')`, e `Comum` possui `administracao: BelongsTo(Administracao::class, 'administracao_id')`.
Para filtrar produtos por administração, filtramos através do relacionamento `comum` (`whereHas('comum', fn ($query) => $query->where('administracao_id', $filters->administrationId))`).

## Component Decisions

1. **`ProductFilters`**:
   - Adicionar `public ?int $administrationId`.
   - Em `fromRequest`: ler `(int) $request->query('administracao_id', 0)`.
   - Em `toQuery`: incluir `administracao_id` quando não nulo.

2. **`LegacyProductBrowserServiceInterface` & `LegacyProductBrowserService`**:
   - Adicionar método `public function administrationOptions(): Collection`.
   - No `paginate`, aplicar `->when($filters->administrationId !== null, fn ($query) => $query->whereHas('comum', fn ($q) => $q->where('administracao_id', $filters->administrationId)))`.

3. **`LegacyProductController`**:
   - No `index` e `verification`: injetar `'administrations' => $this->products->administrationOptions()`.

4. **Blade Views `products.index` e `products.verification`**:
   - Adicionar campo de busca com `data-product-admin-search`, select com `data-product-admin-select`, status acessível com `data-product-admin-status` e script interativo client-side.

5. **Testes**:
   - `ProductFiltersTest`: testes unitários para parsing e serialização de `administrationId`.
   - `LegacyProductBrowserServiceTest`: teste unitário para consulta com filtro de administração e listagem de opções de administração.
   - `LegacyProductControllerTest`: testes de feature para index e verification com filtro `administracao_id` e presença dos novos controles no DOM.
