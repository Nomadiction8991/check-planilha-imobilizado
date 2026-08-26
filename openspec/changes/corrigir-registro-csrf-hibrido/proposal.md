## Why

As rotas AJAX de importação (salvar ações do preview e iniciar processamento) dependem de isenções declaradas no middleware CSRF híbrido. O registro atual usa substituição global, que não afeta o grupo `web` onde o CSRF padrão permanece ativo. Resultado: as rotas isentas retornam 419 em produção para requisições sem cabeçalho Sec-Fetch-Site (ex.: clientes AJAX legados), quebrando o fluxo principal do sistema.

## What Changes

- Registrar o middleware CSRF híbrido no grupo `web` via substituição dentro do grupo, garantindo que a classe ativa na pilha web seja a que declara as rotas isentas.
- Adicionar teste de regressão que valida o registro no grupo web e o comportamento das rotas isentas fora do ambiente de teste.

## Capabilities

### New Capabilities

### Modified Capabilities

- `seguranca-sessao`: O grupo web passa a usar o middleware CSRF híbrido, mantendo proteção padrão nas demais rotas e isenções explícitas apenas nos endpoints AJAX de importação.

## Impact

- bootstrap da aplicação (registro de middleware).
- Comportamento das rotas POST de importação (preview actions e start).
- Nenhuma mudança de schema ou de API pública.
