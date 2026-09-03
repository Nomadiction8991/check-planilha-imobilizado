## Why

A proteção de escopo já cobre a leitura e a escrita de produtos, mas a edição de igrejas e a criação/edição/exclusão de dependências ainda aceitam IDs de administrações ou igrejas fora do escopo quando a requisição é manipulada. Isso permite alterar cadastros auxiliares fora da administração autorizada, mesmo que a listagem esteja filtrada.

## What Changes

- Validar o vínculo da administração escolhida e da igreja alvo com o escopo do usuário antes de atualizar igrejas.
- Validar a igreja escolhida com o escopo antes de criar, atualizar ou excluir dependências.
- Validar separadamente a administração escolhida na edição de igrejas para evitar troca de igreja para outra administração não autorizada.
- Rejeitar requisições fora do escopo com mensagem amigável, sem alterar dados.
- Cobrir os caminhos de escrita de igrejas e dependências com testes de regressão.

## Capabilities

### New Capabilities

- `escrita-igrejas-escopo`: protege a edição de igrejas conforme o escopo de administração do usuário.
- `escrita-dependencias-escopo`: protege criação, edição e exclusão de dependências conforme o escopo de igreja/administração do usuário.

### Modified Capabilities

- `churches`: complementa o comportamento de edição com a regra de escopo administrativo.
- `departments`: complementa o comportamento de criação/edição/exclusão com a regra de escopo da igreja vinculada.

## Impact

Serão afetados os serviços de gerenciamento de igrejas e dependências, o controlador das respectivas operações de escrita e seus testes. Também será criada/ajustada a verificação de escopo reutilizável para esses domínios. Não há alteração de banco, dependência externa ou contrato para administradores (acesso global mantido).
