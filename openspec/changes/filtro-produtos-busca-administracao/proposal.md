# Proposta: Filtro e Busca Progressiva por Administração em Produtos

## Why
Atualmente, as telas de Igrejas, Usuários, Tipos de Bem, Importação e Dependências possuem filtro por administração com campo de busca progressiva instantânea no seletor de administração. No entanto, as telas de listagem de produtos (`/products`) e de verificação de produtos (`/products/verification`) possuem apenas os filtros por igreja, dependência, tipo de bem e status. Em bases com produtos de diversas administrações, administradores e usuários autorizados precisam filtrar os produtos diretamente pela administração de sua igreja vinculada para restringir o universo de dados e agilizar conferências e auditorias.

## What Changes
1. **ProductFilters DTO**: Adicionar a propriedade opcional `administrationId` (`administracao_id`) ao DTO `ProductFilters`, incluindo extração em `fromRequest` e serialização em `toQuery`.
2. **LegacyProductBrowserServiceInterface & Service**: Adicionar suporte ao filtro por `administracao_id` (via `whereHas('comum', fn ($q) => $q->where('administracao_id', $filters->administrationId))`) no método `paginate` e disponibilizar o método `administrationOptions(): Collection` para listar administrações ordenadas por descrição.
3. **LegacyProductController**: Passar `administrations` para as views `products.index` e `products.verification`, preservando os filtros em paginação e links de navegação.
4. **Views `products.index` e `products.verification`**: Adicionar o campo de busca progressiva de administração e o seletor `administracao_id`, seguindo o padrão de design, acessibilidade e UX consolidado do projeto.
5. **Testes Unitários e de Feature**: Cobertura TDD completa para DTO, serviço de browser e controller de produtos nas rotas index e verification.
