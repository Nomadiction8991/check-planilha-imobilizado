# Design — Segurança de sessão e redirects internos

## Abordagem

### Validação de redirect interno (`resolveInternalRedirectTarget`)

Método privado em `LegacyAuthController` que normaliza o destino:

1. `trim()` do valor; vazio → `null` (redirect padrão).
2. Começa com `/`:
   - `//...` (protocol-relative) → `null`;
   - caso contrário → caminho relativo aceito.
3. URL absoluta (`http(s)://...`): `parse_url` e comparação `hash_equals`
   entre o host do candidato e o host do `config('app.url')` (ex.: `checkplanilha.anvy.com.br`).
   - Host igual → reconstrói `path + query + fragment` (redirect interno);
   - host diferente/ausente → `null`.

Usado em `login()` (após `pull('redirect_after_login')`) e `switchChurch()`
(validação do `redirect_to`).

### Rotação de sessão no switchChurch

- `LegacyAuthSessionService::switchChurch`: `Session::regenerate()` após
  `Session::put('comum_id', ...)` — mesma prática já usada em `attempt()`.
- `LegacyRouteCompatibilityController::usersSelectChurch`: passa a depender
  exclusivamente de `$auth->switchChurch()` (que agora regenera), removendo o
  `put('comum_id')` duplicado.

### Logout com defesa em profundidade

`LegacyAuthController::logout` mantém o `forget([...])` explícito das chaves
legadas mesmo após `$this->auth->logout()` (que internamente faz
`Session::invalidate()`). Isso garante o contrato do
`LegacyAuthControllerTest::test_logout_clears_legacy_session_and_redirects`
(que usa mock do serviço) e torna o comportamento independente da
implementação do serviço.

### Logout do acesso público

`PublicAccessController::logout` também chama `$this->auth->logout()` para
invalidar a sessão nativa legada além de limpar as chaves `public_*`.

## Decisões

- **Não** alterar a lógica de autenticação nem o fluxo de credenciais.
- **Não** criar whitelist de rotas — a regra de host único cobre o caso.
- Rotação de sessão apenas no switchChurch (login já regenera em `attempt()`).