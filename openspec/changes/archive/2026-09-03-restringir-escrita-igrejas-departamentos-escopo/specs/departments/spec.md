## ADDED Requirements

### Requirement: Restrição de escopo na escrita de dependências

Ao criar, atualizar ou excluir dependência, o sistema SHALL exigir que toda igreja envolvida (atual da dependência existente e nova igreja informada) esteja dentro do escopo do usuário restrito; fora do escopo a operação SHALL ser rejeitada com mensagem `A igreja selecionada está fora do seu escopo permitido.` sem mutação.

#### Scenario: Criação de dependência fora do escopo é rejeitada

- WHEN a igreja informada para nova dependência está fora do escopo
- THEN a criação SHALL ser rejeitada com status de erro e nenhum registro é criado

#### Scenario: Atualização para igreja fora do escopo é rejeitada

- WHEN a nova igreja informada na atualização está fora do escopo
- THEN a atualização SHALL ser rejeitada com status de erro e o registro permanece inalterado

#### Scenario: Atualização ou exclusão de dependência fora do escopo atual é rejeitada

- WHEN a dependência alvo pertence a igreja fora do escopo
- THEN qualquer tentativa de alteração ou exclusão SHALL ser rejeitada com status de erro
