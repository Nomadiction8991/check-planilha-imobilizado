## Why

As telas administrativas são consultadas principalmente no celular, mas a navegação por teclado e leitores de tela ainda precisa de um ponto de entrada consistente e de foco visual confiável. Melhorar esses fundamentos reduz o tempo para chegar ao conteúdo principal e evita que usuários de teclado se percam em cabeçalhos e filtros repetidos.

## What Changes

- Adicionar um link de salto visível somente ao receber foco, levando diretamente ao conteúdo principal.
- Dar ao conteúdo principal um destino identificável e garantir que o foco seja percebido sem depender de cor ou movimento.
- Padronizar estados `focus-visible` para links, botões, campos, seleções e áreas de texto do layout administrativo.
- Preservar a adaptação mobile existente, alvos de toque e suporte a movimento reduzido.

## Capabilities

### New Capabilities

- `navegacao-acessivel`: oferece salto para o conteúdo principal e foco visual consistente nas telas administrativas.

### Modified Capabilities

- Nenhuma.

## Impact

A mudança afeta o layout Blade compartilhado e os estilos globais das telas administrativas. Não altera rotas, dados, autenticação, contratos de formulário nem dependências externas; a validação será feita por testes de renderização e lint das views alteradas.
