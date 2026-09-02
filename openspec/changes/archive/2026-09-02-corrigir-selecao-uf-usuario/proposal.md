## Why

O formulário de usuários exibe os estados por código, mas a opção selecionada é calculada como se a lista fosse posicional. Como a configuração é associativa, a edição perde a UF já cadastrada e o usuário precisa escolhê-la novamente; isso reduz a confiança no cadastro e pode enviar uma alteração acidental.

## What Changes

- Corrigir a renderização da UF no formulário de criação e edição para usar código e nome a partir da lista associativa de estados.
- Preservar a UF existente ou informada anteriormente quando o formulário for reexibido após erro de validação.
- Cobrir a seleção correta por teste de apresentação do formulário.

## Capabilities

### New Capabilities

<!-- Nenhuma capacidade nova; a mudança corrige um requisito existente. -->

### Modified Capabilities

- `users`: a UF do endereço deve permanecer selecionada e ser apresentada com seu nome correspondente no formulário de usuário.

## Impact

A alteração afeta somente a view compartilhada dos formulários de usuário e os testes de gerenciamento de usuários. Não muda banco, rotas, contratos HTTP ou dependências; o campo continua enviando o código de duas letras usado pela validação e pelo cadastro legado.
