## Why

A listagem de produtos mostra a ação “Editar” para qualquer usuário que consiga consultar o inventário, mesmo quando sua permissão não inclui alteração. Isso cria uma expectativa falsa, aumenta cliques que terminarão em bloqueio e deixa a diferença entre consultar e modificar pouco clara, especialmente no celular.

## What Changes

- Exibir a ação de edição na listagem somente para administradores ou usuários com a permissão `products.edit`.
- Exibir a ação de edição na verificação somente para administradores ou usuários com `products.edit`, sem alterar o checklist de usuários que já têm acesso à tela.
- Para usuários sem edição, apresentar um estado explícito de consulta em vez de um link inoperante, mantendo a identificação do produto.
- Preservar a autorização no servidor; a mudança de interface não substitui o middleware das rotas de edição.

## Capabilities

### New Capabilities

Nenhuma.

### Modified Capabilities

- `produtos-listagem`: a listagem e a verificação não devem oferecer edição a usuários sem permissão de alteração.

## Impact

- Views de listagem e verificação de produtos.
- Nenhuma alteração de banco, rota, contrato ou dependência.
- Testes de renderização das duas telas para cenários com e sem permissão.
