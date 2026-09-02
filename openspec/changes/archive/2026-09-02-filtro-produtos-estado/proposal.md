# Proposta de Mudança: Filtro por Estado (UF) na Listagem de Produtos

## Intenção
Permitir que os usuários e administradores filtrem a listagem e tela de verificação de produtos do inventário por estado (UF) da igreja associada (`comum.estado`), alinhando a experiência às listagens de igrejas, dependências, usuários, administrações e tipos de bens.

## Escopo
1. Estender `ProductFilters` (DTO) com a propriedade opcional `public ?string $state`, populada a partir do parâmetro de consulta `estado`.
2. Atualizar o método `toQuery()` de `ProductFilters` para incluir `estado` quando definido.
3. Atualizar `LegacyProductBrowserService::paginate` com cláusula `when($filters->state !== null && $filters->state !== '', ...)` filtrando via relacionamento `comum` (`whereHas('comum', fn ($q) => $q->where('estado', $filters->state))`).
4. Injetar a lista de estados (`(array) config('brazil.states', [])`) nas views `products.index` e `products.verification` via `LegacyProductController`.
5. Adicionar o dropdown de seleção de Estado (UF) nas seções de filtro das views de produtos.
6. Cobrir as alterações com testes unitários e de feature via TDD.
