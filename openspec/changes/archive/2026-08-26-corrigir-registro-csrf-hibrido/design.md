# Design: corrigir-registro-csrf-hibrido

## Contexto

O Laravel 11+ registra middlewares via `Middleware $middleware` no bootstrap. O método `replace()` afeta apenas a pilha global (`getGlobalMiddleware()`), enquanto o grupo `web` é montado por `getMiddlewareGroups()`, que só respeita `replaceInGroup('web', ...)`. Com `replace()`, a requisição web real continua usando o CSRF padrão, ignorando as isenções do híbrido.

## Decisões

- Usar `$middleware->replaceInGroup('web', PreventRequestForgery::class, HybridPreventRequestForgery::class)` para que a substituição ocorra dentro do grupo onde o middleware realmente roda.
- Manter o import da classe base para referência de tipo.
- Teste de regressão inspeciona o grupo via router e exercita uma rota isenta com ambiente forçado para produção.

## Riscos

- Baixo: o middleware híbrido estende o padrão e delega ao comportamento original quando não há isenção ou quando o token confere.
