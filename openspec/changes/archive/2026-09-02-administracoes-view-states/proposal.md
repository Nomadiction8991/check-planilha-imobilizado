# Proposta: Passagem explícita de lista de estados para views de administrações

## Why
Padronizar a injeção da lista de estados (`states`) a partir do controller `LegacyAdministrationController` para as views de listagem e edição de administrações, garantindo consistência com os outros módulos do sistema (`LegacyChurchController`, `LegacyProductController`, `LegacyDepartmentController`, `LegacyUserController`, `LegacyAssetTypeController` e `LegacyReportController`) e facilitando testes unitários e de integração de interface.

## What Changes
- Passar `'states' => (array) config('brazil.states', [])` nos métodos `index`, `create` e `edit` do `LegacyAdministrationController`.
- Atualizar a view `administrations.index`, `administrations.create` e `administrations.edit` para consumir a variável `$states` de forma consistente e com fallback seguro para `config('brazil.states', [])`.
- Atualizar e expandir a cobertura de testes em `LegacyAdministrationControllerTest` para verificar a presença e integridade de `states`.
