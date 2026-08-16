# Tasks — Segurança de sessão e redirects internos

- [x] Teste RED: login rejeita `redirect_after_login` protocol-relative
- [x] Teste RED: login rejeita URL absoluta de outro host (fallback interno)
- [x] Implementar `resolveInternalRedirectTarget` em `LegacyAuthController`
- [x] Usar o validador em `login()` e `switchChurch()`
- [x] Teste RED: switchChurch rejeita `redirect_to` externo
- [x] Adicionar `Session::regenerate()` em `LegacyAuthSessionService::switchChurch`
- [x] Teste RED: switchChurch regenera ID de sessão (serviço e endpoint legado)
- [x] Remover `put('comum_id')` duplicado em `LegacyRouteCompatibilityController`
- [x] Manter `forget([...])` explícito no `LegacyAuthController::logout`
- [x] Teste: logout invalida sessão e remove chaves legadas
- [x] Chamar `$this->auth->logout()` no `PublicAccessController::logout`
- [x] `php -l` em todos os arquivos PHP alterados
- [x] Suíte de testes (feature/auth) verde