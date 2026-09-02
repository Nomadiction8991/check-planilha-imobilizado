# Design: Padronização de Estados nas Views de Tipos de Bens

## Abordagem Técnica
Injetar `'states' => (array) config('brazil.states', [])` nos retornos dos métodos `create` e `edit` da classe `LegacyAssetTypeController`.

Isso segue rigorosamente a convenção estabelecida em `LegacyDepartmentController`, `LegacyAdministrationController`, `LegacyChurchController` e `LegacyUserController`.

## Impacto
- Sem quebra de compatibilidade.
- Maior consistência entre todas as views e controllers do ecossistema de migração.
