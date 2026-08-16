# seguranca-sessao Specification

## Purpose
TBD - created by archiving change seguranca-sessao-redirects. Update Purpose after archive.
## Requirements
### Requirement: Login rejeita redirects externos
O sistema SHALL redirecionar apenas para destinos internos após login bem-sucedido.
Destinos protocol-relative (`//host`) e URLs absolutas de hosts diferentes do
`APP_URL` SHALL ser ignorados, caindo no dashboard padrão.

#### Scenario: redirect_after_login com URL protocol-relative
- GIVEN uma sessão com `redirect_after_login` igual a `//evil.com/phish`
- WHEN o usuário autentica com credenciais válidas
- THEN o sistema redireciona para o dashboard
- AND a chave `redirect_after_login` é removida da sessão

#### Scenario: redirect_after_login com URL absoluta de outro host
- GIVEN uma sessão com `redirect_after_login` igual a `https://evil.com/phish`
- WHEN o usuário autentica com credenciais válidas
- THEN o sistema redireciona para o dashboard

#### Scenario: redirect_after_login com caminho interno
- GIVEN uma sessão com `redirect_after_login` igual a `/igrejas`
- WHEN o usuário autentica com credenciais válidas
- THEN o sistema redireciona para `/igrejas`

### Requirement: Troca de igreja valida destino de retorno
O sistema SHALL validar `redirect_to` no switchChurch com as mesmas regras de
redirect interno do login.

#### Scenario: redirect_to absoluto externo no switchChurch
- GIVEN um usuário autenticado com sessão e igrejas permitidas
- WHEN o usuário troca de igreja enviando `redirect_to` igual a `//evil.com`
- THEN o sistema redireciona de volta (comportamento padrão `back()`)

### Requirement: Troca de igreja rotaciona o ID de sessão
O serviço `LegacyAuthSessionService::switchChurch` SHALL chamar
`Session::regenerate()` após gravar o novo `comum_id`.

#### Scenario: switchChurch gera novo ID de sessão
- GIVEN um usuário autenticado
- WHEN o serviço troca a igreja ativa
- THEN o ID da sessão é regenerado

#### Scenario: endpoint legado usersSelectChurch rotaciona sessão
- GIVEN um usuário autenticado
- WHEN o endpoint de compatibilidade `usersSelectChurch` troca a igreja
- THEN o ID da sessão é regenerado

### Requirement: Logout limpa chaves legadas de sessão
O `LegacyAuthController::logout` SHALL remover explicitamente as chaves legadas
(`usuario_id`, `legacy_permissions`, etc.) da sessão após invalidar, garantindo
que nenhuma chave sobreviva mesmo quando o serviço é substituído por mock.

#### Scenario: logout remove chaves legadas
- GIVEN uma sessão com chaves legadas preenchidas
- WHEN o usuário faz logout
- THEN a sessão não contém `usuario_id`, `usuario_nome`, `usuario_email`,
  `comum_id`, `administracao_id`, `administracoes_permitidas`, `is_admin`
  nem `legacy_permissions`

