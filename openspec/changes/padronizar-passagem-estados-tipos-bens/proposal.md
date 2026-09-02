# Proposta: Padronizar passagem de estados para as views de Tipos de Bens

## Motivação
Nas views de cadastros e manutenções (`departments.create`, `departments.edit`, `administrations.create`, `administrations.edit`, `users.create`, `users.edit`, `churches.edit`), os dados de estados brasileiros são padronizados e passados via controller para as views através de `'states' => (array) config('brazil.states', [])`.

Em `LegacyAssetTypeController`, os métodos `create` e `edit` não enviavam a variável `states` para as views `asset-types.create` e `asset-types.edit`, embora o `index` já enviasse. Além disso, as views `asset-types.create` e `asset-types.edit` se beneficiam de suporte consistente a estados e compatibilidade de renderização caso novos seletores ou referências de UF sejam incorporados, mantendo coerência arquitetural com os demais módulos do sistema.

## Escopo
- Passar `'states' => (array) config('brazil.states', [])` nos métodos `create` e `edit` do `LegacyAssetTypeController`.
- Atualizar testes de feature em `LegacyAssetTypeControllerTest` para garantir a presença da variável `states` nas views de `create` e `edit`.
- Garantir que toda a suíte continue verde.
