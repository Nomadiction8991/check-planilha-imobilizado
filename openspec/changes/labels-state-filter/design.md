# Design Técnico: Filtro por Estado na Seleção de Etiquetas

## Abordagem
1. Atualizar a assinatura de `LegacyAuthSessionServiceInterface::availableChurches` para aceitar `?string $state = null` opcional, mantendo total retrocompatibilidade.
2. Atualizar a implementação `LegacyAuthSessionService::availableChurches` para aplicar `where('estado', $state)` quando `$state` for informado.
3. No `LegacyRouteCompatibilityController::labels`, capturar `estado` da requisição e repassar para `availableChurches($administrationId, $state)`, injetando `states` e `selectedState` para a view `labels.index`.
4. Atualizar a view `resources/views/labels/index.blade.php` com o campo de seleção de estado.
5. Escrever testes de feature garantindo o comportamento correto do filtro de estado.
