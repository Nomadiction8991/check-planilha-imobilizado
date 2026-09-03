## Why

A tela de edição mostra como referência o tipo de bem e a dependência originais mesmo quando o produto já possui uma classificação editada. Isso contradiz os valores selecionados para a nova edição e aumenta o risco de o usuário corrigir ou conferir o patrimônio com base em uma classificação desatualizada, especialmente no uso mobile.

## What Changes

- Exibir no bloco de referência da edição o tipo de bem e a dependência atualmente válidos.
- Priorizar as relações editadas quando o produto estiver marcado como editado e houver dados exibíveis.
- Usar as relações originais como fallback quando a edição não tiver uma relação válida.
- Carregar as relações editadas junto com o produto, sem consultas adicionais durante a renderização.
- Cobrir a preparação da tela e a renderização com testes automatizados.

## Capabilities

### New Capabilities

### Modified Capabilities

- `produtos-listagem`: a tela de edição passa a apresentar a classificação atual do produto no bloco de referência, mantendo fallback para a classificação original.

## Impact

Serão afetados o controlador de edição, a view de produtos e os testes de gerenciamento de produtos. Não haverá alteração de banco, rota, permissão ou dependência externa. A seleção dos novos valores e o comportamento de atualização permanecem inalterados.
