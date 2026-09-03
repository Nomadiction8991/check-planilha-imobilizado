## ADDED Requirements

### Requirement: Filtro por dependência na tela de etiquetas

O sistema SHALL permitir filtrar os produtos marcados para impressão por dependência na tela de etiquetas (`GET /labels`). As opções de dependência e os produtos exibidos SHALL representar a dependência atual de cada produto: a dependência editada somente prevalece quando o produto está marcado como editado e a relação editada possui descrição exibível; caso contrário, a dependência original SHALL ser usada como fallback.

#### Scenario: Produto editado usa a dependência editada

- **GIVEN** um produto marcado para impressão e marcado como editado, com dependência original "SALAO" e dependência editada "SECRETARIA"
- **WHEN** o usuário seleciona "SECRETARIA" no filtro de dependência
- **THEN** o produto é exibido e sua dependência apresentada é "SECRETARIA"
- **AND** "SALAO" não é usado como sua dependência atual

#### Scenario: Produto não editado ignora vínculo editado residual

- **GIVEN** um produto marcado para impressão e não marcado como editado, com dependência original "SALAO" e um vínculo editado residual para "SECRETARIA"
- **WHEN** o usuário seleciona "SALAO" no filtro de dependência
- **THEN** o produto é exibido com a dependência "SALAO"
- **AND** o produto não é exibido ao selecionar "SECRETARIA"

#### Scenario: Produto editado sem descrição editada usa a original

- **GIVEN** um produto marcado para impressão e marcado como editado, com dependência original "SALAO" e vínculo editado inexistente ou sem descrição
- **WHEN** o usuário seleciona "SALAO" no filtro de dependência
- **THEN** o produto é exibido com a dependência "SALAO"
- **AND** a lista de dependências não apresenta uma opção vazia criada pelo vínculo inválido

#### Scenario: Opções representam somente dependências com produtos elegíveis

- **GIVEN** produtos marcados e desmarcados para impressão em várias dependências
- **WHEN** o usuário abre a tela de etiquetas para uma igreja
- **THEN** o seletor de dependência contém somente dependências atuais associadas a produtos marcados para impressão
- **AND** cada opção possui identificador e descrição correspondentes à mesma dependência usada pela filtragem

#### Scenario: Filtro sem dependência mantém todos os produtos marcados

- **GIVEN** uma igreja com produtos marcados para impressão em mais de uma dependência
- **WHEN** o usuário mantém "Todas as dependências" selecionado
- **THEN** todos os produtos marcados para impressão são exibidos
- **AND** cada produto mostra sua dependência atual conforme a regra de fallback
