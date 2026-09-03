## Why

Ao trocar rapidamente o estado em um formulário de cadastro, uma resposta lenta de uma consulta anterior pode chegar depois da escolha atual e substituir a lista de cidades correta. Isso deixa o campo de cidade mostrando opções de outra UF e pode induzir o usuário a salvar uma localização incoerente.

## What Changes

- Cancelar ou invalidar consultas anteriores de estados e cidades quando uma nova carga for iniciada.
- Ignorar respostas que não correspondem mais à seleção atual do formulário.
- Manter o estado visual do campo de cidade consistente durante carregamento, troca de UF, erro e retorno vazio.
- Cobrir a proteção contra respostas obsoletas no teste do comportamento do formulário.

## Capabilities

### New Capabilities

### Modified Capabilities

- `importacao-imobilizado`: os formulários de localização devem manter as cidades sincronizadas com a UF atualmente selecionada, mesmo quando as consultas retornarem fora de ordem.

## Impact

A mudança afeta somente o script compartilhado de localidades usado pelos formulários e sua cobertura de teste. Não altera os endpoints, o formato das respostas, os dados persistidos ou o banco de produção.
