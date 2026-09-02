# Design Técnico: Filtro de Administração nas Etiquetas

## Contexto & Arquitetura
A tela de etiquetas é servida pela action `labels` em `LegacyRouteCompatibilityController`.
Ela carrega as congregações a partir de `LegacyAuthSessionServiceInterface::availableChurches(?int $administrationId = null)`.

## Decisões
1. Extender ou parametrizar `availableChurches(?int $administrationId = null)` em `LegacyAuthSessionServiceInterface` e `LegacyAuthSessionService` para aceitar filtro opcional de `administracao_id`.
2. Adicionar `availableAdministrations(): Collection` em `LegacyAuthSessionServiceInterface` e `LegacyAuthSessionService` retornando coleções de `Administracao` ordenadas por descrição.
3. Injetar `administrations` e `selectedAdministrationId` na view `labels.index`.
4. Adicionar os campos de busca textual de administração (`data-labels-admin-search`, `data-labels-admin-select`, `data-labels-admin-status`) mantendo a mesma semântica acessível das outras telas.
5. Adicionar testes unitários e de feature cobrindo a nova funcionalidade e filtros.
