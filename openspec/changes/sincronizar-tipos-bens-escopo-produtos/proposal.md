## Why

A listagem de produtos permite consultar bens de várias administrações autorizadas, mas o seletor de tipos de bem usado nos filtros e cadastros mostra apenas a administração ativa. Usuários restritos não conseguem refinar ou cadastrar produtos de administrações adicionais que já estão no escopo deles.

## What Changes

- Fazer as opções de tipos de bem nas telas de produtos respeitarem todas as administrações permitidas ao usuário restrito, além dos tipos compartilhados.
- Manter administradores com acesso global e preservar a compatibilidade com esquemas que ainda não possuem vínculo administrativo em tipos de bem.
- Garantir que o filtro e os formulários de produtos recebam o mesmo conjunto coerente de tipos de bem que a consulta autorizada pode retornar.
- Cobrir a combinação de administração ativa, administrações adicionais permitidas, administração não permitida e tipos compartilhados.

## Capabilities

### New Capabilities

### Modified Capabilities

- `produtos-listagem`: opções de tipos de bem devem acompanhar todas as administrações permitidas na consulta de produtos, sem expor tipos fora do escopo.

## Impact

A mudança afeta a consulta das opções de tipos de bem usada pelo navegador de produtos e seus testes, além da especificação de listagem. Não altera rotas, banco, parâmetros HTTP ou o acesso global de administradores; o comportamento continua compatível quando a coluna de administração não existe.
