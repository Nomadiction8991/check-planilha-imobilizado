# Proposta: Segurança de sessão e redirects internos

## Por quê

O fluxo de login e troca de igreja aceita `redirect_after_login` / `redirect_to`
fornecidos na sessão ou via formulário. A validação atual apenas exige que o
valor **comece com `/`**, o que permite open redirect via URLs
protocol-relative (`//evil.com/phish`) e URLs absolutas externas
(`https://evil.com/...`). Um atacante pode usar isso para phishing após login.

Além disso, a troca de igreja (`switchChurch`) não rotaciona o ID de sessão,
deixando a sessão vulnerável a fixação após troca de contexto de igreja.

## O que

1. Validar que todo redirect pós-login / pós-troca-de-igreja é **interno**:
   - Caminho relativo simples começando com `/` (exceto `//` protocol-relative);
   - URL absoluta **somente** se o host for exatamente o `APP_URL` da aplicação;
   - Caso contrário, cair no redirect padrão (dashboard / voltar).
2. Rotacionar o ID de sessão (`Session::regenerate()`) ao trocar de igreja,
   tanto no serviço canônico (`LegacyAuthSessionService`) quanto nos endpoints
   legados de compatibilidade.
3. Garantir que o logout limpe as chaves legadas da sessão mesmo quando o
   serviço interno é mockado (defesa em profundidade).

## Escopo

- `LegacyAuthController` (login, logout, switchChurch)
- `LegacyRouteCompatibilityController::usersSelectChurch`
- `PublicAccessController::logout`
- `LegacyAuthSessionService::switchChurch`

## Fora de escopo

- Redirecionamentos de relatórios e telas internas (não usam valores do usuário)
- Autenticação em si (fluxo de credenciais)