# Design: Passagem de Estados nas Views de Administração

## Contexto & Abordagem
Seguindo o padrão de design adotado em todos os outros cadastros (`LegacyChurchController`, `LegacyProductController`, `LegacyDepartmentController`, `LegacyUserController`, etc.), o `LegacyAdministrationController` deve repassar explicitamente o array de estados brasileiros para o template Blade sob a chave `states`.

As views `administrations.index`, `administrations.create` e `administrations.edit` usarão `$states ?? config('brazil.states', [])` de modo limpo e direto.

## Testes & Validação
- Testes de Feature em `LegacyAdministrationControllerTest` validando que a view recebe a variável `states`.
- Execução completa da suíte de testes do PHPUnit.
