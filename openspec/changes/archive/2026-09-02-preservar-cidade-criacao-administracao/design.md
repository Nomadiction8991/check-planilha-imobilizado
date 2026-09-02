## Context

A view de criação de administração já preserva os campos simples com `old()`, mas o select de cidade é preenchido de forma assíncrona pelo asset compartilhado de localidades. A view de edição já fornece a cidade inicial por `data-selected-city`, e o script aceita esse valor ao carregar os municípios. A mudança deve reutilizar esse contrato, sem criar uma segunda implementação para o cadastro de administração.

## Goals / Non-Goals

**Goals:**

- Entregar à view de criação o valor anterior da cidade por meio do mesmo atributo usado na edição.
- Fazer o teste funcional provar que o valor chega escapado ao HTML e que o asset compartilhado está presente.
- Preservar o comportamento de troca de estado: uma nova escolha continua limpando a cidade anterior.

**Non-Goals:**

- Alterar o endpoint de localidades, o formato da resposta ou a persistência da administração.
- Alterar a tela de edição, que já possui o contrato necessário.
- Criar dependência ou pipeline separado para testar JavaScript.

## Decisions

A view de criação usará `old('cidade')` em `data-selected-city` no select desabilitado. O helper `old()` já é a fonte de verdade do Laravel para repopular formulários após redirecionamento de validação e a view de edição demonstra que o asset lê esse atributo antes de solicitar as cidades.

O script compartilhado continuará resolvendo o valor com `config.selectedCity`, depois com `dataset.selectedCity`, e só então com o valor atual do select. Assim, a cidade antiga é aplicada apenas na inicialização; ao mudar o estado, o listener chama o carregamento sem cidade selecionada e evita manter uma opção inválida de outro estado.

A cobertura ficará no teste funcional existente da tela de administração, verificando o atributo renderizado com uma cidade acentuada e o carregamento do asset. O teste exercita o contrato entre view e navegador sem duplicar chamadas externas nem transformar o asset em código específico de uma tela.

## Risks / Trade-offs

- [Risco] A cidade antiga pode não existir mais na resposta externa → a lista será exibida sem seleção, permitindo uma escolha válida; a validação do formulário continua impedindo o envio vazio.
- [Risco] O valor renderizado conter caracteres especiais → Blade escapa o atributo HTML, evitando markup inválido e injeção; o teste usa uma cidade acentuada para cobrir o caminho real.
- [Trade-off] O teste funcional não executa o JavaScript em um navegador → o comportamento do asset já é compartilhado e o teste valida o contrato de entrada; a lógica de inicialização permanece pequena e coberta pelo uso existente nas telas de edição.

## Migration Plan

Não há migração de dados. Após a publicação, a tela de criação passa a carregar automaticamente a cidade informada quando retornar com dados antigos. O rollback consiste em remover o atributo da view; nenhuma alteração de banco ou configuração é necessária.
