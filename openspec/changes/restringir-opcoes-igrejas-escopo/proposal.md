## Why

A tela de igrejas é usada por usuários com escopo administrativo restrito, mas atualmente carrega todas as igrejas e administrações disponíveis. Isso expõe opções fora da autorização do usuário e permite que filtros e a navegação apresentem dados que ele não deveria consultar.

## What Changes

- Restringir a listagem de igrejas às administrações permitidas ao usuário autenticado.
- Restringir as opções de administração do filtro ao mesmo escopo.
- Manter administradores globais com acesso a todas as igrejas e administrações.
- Tratar filtros por administração fora do escopo como resultado vazio, sem revelar igrejas externas.
- Cobrir usuários restritos com administração principal e administrações adicionais, além do acesso global.

## Capabilities

### New Capabilities

### Modified Capabilities

- `churches`: a listagem e as opções de filtro passam a respeitar o escopo administrativo do usuário.

## Impact

A mudança afeta a consulta e as opções fornecidas pelo serviço de navegação de igrejas e os testes correspondentes. Não altera rotas, banco de dados ou o comportamento de administradores globais; filtros, paginação e busca textual continuam usando os mesmos parâmetros.