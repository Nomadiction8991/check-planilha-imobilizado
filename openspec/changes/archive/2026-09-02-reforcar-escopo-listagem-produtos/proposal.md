## Why

As listagens de produtos e a verificação exibem opções e aceitam identificadores de qualquer igreja, embora o usuário não administrador possa estar autorizado somente para uma ou mais administrações. Isso expõe inventário fora do escopo e permite que filtros, ações e links operacionais sejam usados com uma igreja não autorizada. O fluxo principal precisa aplicar o mesmo limite de administração já usado na importação e no gerenciamento de usuários.

## What Changes

- Restringir a consulta paginada de produtos ao conjunto de administrações permitidas ao usuário autenticado quando ele não for administrador.
- Restringir as opções de igrejas e dependências apresentadas nas telas de produtos ao mesmo escopo.
- Manter administradores com acesso global e preservar filtros válidos, paginação e comportamento existente.
- Cobrir com testes de unidade e integração o isolamento de produtos entre administrações e a ausência de opções fora do escopo.

## Capabilities

### New Capabilities

### Modified Capabilities

- `produtos-listagem`: a listagem, a verificação e seus seletores SHALL respeitar o escopo de administrações permitidas ao usuário autenticado.

## Impact

A alteração afeta o serviço de consulta de produtos, as opções de igreja/dependência fornecidas às views e a cobertura PHPUnit correspondente. Não altera o schema do banco, endpoints públicos ou o acesso de administradores.
