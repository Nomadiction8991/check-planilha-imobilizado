## Why

Ao criar uma administração, o formulário envia o estado escolhido, mas o campo de cidade é iniciado sem um valor selecionado e não carrega uma cidade antiga após uma falha de validação. A pessoa precisa escolher novamente a cidade, mesmo quando o restante do formulário foi preservado, aumentando o risco de salvar uma localização diferente.

## What Changes

- Preservar a cidade informada anteriormente no formulário de criação de administração.
- Permitir que o carregamento de localidades selecione automaticamente essa cidade depois que a lista do estado estiver disponível.
- Cobrir o contrato da view e do script de localidades com teste funcional e validação do comportamento existente de edição.

## Capabilities

### New Capabilities

<!-- Nenhuma capacidade nova; a mudança aprimora um requisito existente. -->

### Modified Capabilities

- `administrations`: o formulário de criação deve manter a cidade informada quando for reexibido.

## Impact

A alteração fica restrita à view de criação de administrações e ao contrato já utilizado pelo asset de localidades no navegador. Não altera banco, rotas, regras de validação, API externa ou dependências; o fluxo de edição continua usando a cidade cadastrada como valor inicial.
