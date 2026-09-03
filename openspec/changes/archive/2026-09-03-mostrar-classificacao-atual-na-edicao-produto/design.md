## Context

A tela de edição já carrega as relações originais para preencher o bloco somente leitura e mantém as relações editadas para os campos do novo cadastro. A listagem e a verificação já centralizam a regra de prioridade em `LegacyProductClassificationSupport`; a edição deve reutilizar essa regra para evitar divergência entre telas. O carregamento do produto usa model binding, portanto as relações necessárias podem ser incluídas no `loadMissing` antes de montar a view.

## Goals / Non-Goals

**Goals:**

- Fazer o bloco "Valores atuais" representar a mesma classificação atual mostrada nas outras telas.
- Preservar o fallback original para produtos sem edição válida.
- Evitar lazy loading durante a renderização da view.
- Manter intacta a seleção e o envio dos novos valores.

**Non-Goals:**

- Alterar o modelo de dados, as rotas, permissões ou regras de atualização.
- Reorganizar a composição visual da tela além dos rótulos necessários para deixar a classificação atual explícita.
- Alterar a classificação usada por listagem, verificação, busca ou filtros, que já está coberta pela especificação existente.

## Decisions

### Reutilizar o suporte compartilhado de classificação

A view chamará `LegacyProductClassificationSupport` para obter o rótulo atual do tipo e a descrição atual da dependência. Essa decisão mantém uma única regra para listagem, verificação e edição, incluindo a validação de relação editada e o fallback. Como alternativa, poderíamos duplicar a escolha diretamente no Blade, mas isso permitiria que uma tela voltasse a exibir valores originais por engano.

### Carregar as duas relações editadas no controlador

O método de edição incluirá `editadoTipoBem` e `editadoDependencia` no carregamento antecipado, com apenas as colunas necessárias. Assim, a view não dispara consultas N+1 e o suporte recebe todas as informações necessárias. Como alternativa, seria possível consultar os vínculos no Blade, mas isso mistura acesso a dados com apresentação e torna o custo imprevisível.

### Rotular a referência como classificação atual

Os campos serão apresentados com rótulos que indiquem o estado atual, sem mudar o contrato dos campos editáveis. A distinção reduz a ambiguidade quando o produto já foi editado e preserva o caráter somente leitura do bloco.

## Risks / Trade-offs

- [Risco] Um vínculo editado válido pode não estar presente na lista de opções disponível para edição. → A tela de referência não depende da lista de opções; ela usa a relação carregada do produto e mantém o fallback original.
- [Risco] Alterar apenas a view pode ocultar uma consulta lazy acidental. → O teste de controlador/view verificará a presença da classificação editada, e o controlador carregará explicitamente as quatro relações usadas pela tela.
- [Risco] O helper pode receber modelos parcialmente montados em testes. → Os testes manterão relações originais e editadas explícitas e cobrirão produto sem edição e produto editado.

## Migration Plan

Nenhuma migração de banco é necessária. Após a implementação, executar os testes de gerenciamento de produtos, validar os arquivos PHP alterados, limpar caches somente se necessário e verificar a página de login e a tela de edição em runtime. O rollback consiste em reverter o commit caso a renderização apresente regressão.
