## ADDED Requirements

### Requirement: Restrição de escopo na edição de igrejas

Ao atualizar uma igreja, o sistema SHALL aplicar a regra de escopo administrativo vigente: usuários restritos só podem editar igrejas de sua administração autorizada e só podem reatribuir para administrações autorizadas; fora do escopo a operação SHALL ser rejeitada com mensagem `A igreja selecionada está fora do seu escopo permitido.` ou `A administração selecionada está fora do seu escopo permitido.` sem alterar dados.

#### Scenario: Edição de igreja fora do escopo é rejeitada

- WHEN a igreja alvo pertence a administração fora do escopo do usuário restrito
- THEN a edição SHALL ser rejeitada com status de erro e o registro permanece inalterado

#### Scenario: Troca de administração fora do escopo é rejeitada

- WHEN a nova administração informada está fora do escopo do usuário restrito
- THEN a edição SHALL ser rejeitada com status de erro e o registro permanece inalterado
