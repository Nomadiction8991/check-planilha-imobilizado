# Tarefas de Implementação

- [x] Atualizar `LegacyAuthSessionServiceInterface` e `LegacyAuthSessionService` para suportar `availableChurches(?int $administrationId = null)` e `availableAdministrations(): Collection` <!-- id: 1 -->
- [x] Atualizar `LegacyRouteCompatibilityController::labels` para receber `administracao_id` e repassar administrações e igrejas filtradas à view <!-- id: 2 -->
- [x] Atualizar `resources/views/labels/index.blade.php` com busca textual e select de administração <!-- id: 3 -->
- [x] Atualizar testes existentes e criar novos testes cobrindo o filtro e renderização <!-- id: 4 -->
- [x] Validar com `openspec validate` e suíte PHPUnit <!-- id: 5 -->
