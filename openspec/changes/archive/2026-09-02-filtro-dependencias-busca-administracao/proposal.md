# Proposta: Filtro e Busca Progressiva por Administração em Dependências

## Why
Atualmente, as telas de Igrejas, Usuários e Tipos de Bem possuem filtro por administração com campo de busca progressiva instantânea no seletor de administração. No entanto, a tela de listagem de dependências (`/departments`) possui apenas o filtro por igreja e busca por descrição da dependência, sem a opção de filtrar diretamente por administração (através do vínculo da igreja pertencente à administração). Em bases com muitas igrejas e dependências espalhadas por diversas administrações, administradores precisam selecionar uma administração para restringir o universo de dados e agilizar a conferência.

## What Changes
1. **DepartmentFilters DTO**: Adicionar o campo opcional `administrationId` (`administracao_id`) ao DTO `DepartmentFilters`, incluindo extração em `fromRequest` e serialização em `toQuery`.
2. **LegacyDepartmentBrowserServiceInterface & Service**: Adicionar suporte a filtro por `administracao_id` (via `whereHas('comum', fn ($q) => $q->where('administracao_id', $filters->administrationId))`) e disponibilizar o método `administrationOptions(): Collection` para alimentar as opções do select.
3. **LegacyDepartmentController**: Passar `administrations` para a view `departments.index` e manter os filtros com paginação.
4. **View `departments.index`**: Adicionar o campo de busca progressiva de administração e o seletor `administracao_id`, seguindo o padrão de design e UX consolidado das outras telas (`asset-types`, `users`, `churches`).
5. **Testes Unitários e de Feature**: Cobertura TDD completa para DTO, serviço de browser e controller de dependências.
