# Proposta de Centralização de Confirmação em Formulários Destrutivos de Cadastros

## Why
Padronizar a confirmação de exclusão nas páginas de listagem de Administrações, Dependências e Tipos de Bem utilizando o atributo declarativo `data-confirm="..."`, em conformidade com o listener global do layout (`resources/views/layouts/migration.blade.php`), eliminando handlers inline `onclick="return confirm(...)` legados e garantindo comportamento consistente, acessível e testável.

## What Changes
1. Atualizar as views de listagem:
   - `resources/views/administrations/index.blade.php`
   - `resources/views/departments/index.blade.php`
   - `resources/views/asset-types/index.blade.php`
2. Adicionar testes unitários/feature que asseguram o uso de `data-confirm` nas 3 views e a ausência de `onclick="return confirm(...)"`.
3. Validar a suíte completa de testes.
