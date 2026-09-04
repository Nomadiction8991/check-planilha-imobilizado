# etiquetas-listagem Specification

## Purpose
Filtros da tela de etiquetas com seleção de igreja eficiente por busca filtrável client-side.
## Requirements
### Requirement: Busca filtrável de igreja nos filtros de etiquetas

O sistema SHALL exibir, na tela de etiquetas (`GET /labels`), um campo de busca digitável associado ao seletor de igreja (`comum_id`) que filtra em tempo real as opções por correspondência case-insensitive no texto exibido (codigo - descricao), sem recarregar a página e sem alterar o conjunto retornado pelo servidor. A seleção de uma igreja visível SHALL submeter automaticamente os filtros enviados ao servidor, e a limpeza da busca local SHALL restaurar as opções permitidas e remover uma seleção que tenha ficado incompatível antes da submissão automática.

#### Scenario: Campo de busca está visível

- **WHEN** o usuário visita `/labels`
- **THEN** a página exibe um campo de busca digitável associado ao seletor de igreja, visível e rotulado de forma acessível

#### Scenario: Digitação filtra opções visíveis

- **WHEN** o usuário digita um termo que coincide com parte do texto de algumas igrejas
- **THEN** apenas as opções correspondentes permanecem visíveis/selecionáveis e as demais são ocultadas sem submeter o formulário enquanto a busca local está sendo usada

#### Scenario: Seleção de igreja atualiza os resultados

- **WHEN** o usuário escolhe uma igreja no seletor
- **THEN** o formulário é submetido automaticamente com o identificador da igreja selecionada

#### Scenario: Limpar busca restaura todas as opções

- **WHEN** o usuário limpa o campo de busca
- **THEN** todas as igrejas voltam a ficar visíveis, o placeholder "Selecione uma igreja" permanece disponível e uma seleção ocultada é removida antes da atualização automática

#### Scenario: Nenhum resultado exibe mensagem e desabilita seletor

- **WHEN** o termo digitado não corresponde a nenhuma igreja
- **THEN** o sistema exibe mensagem acessível indicando ausência de resultados e desabilita temporariamente o seletor até que a busca seja ajustada

### Requirement: Filtro por administração na seleção de congregações para etiquetas

O sistema SHALL permitir filtrar as congregações disponíveis na tela de etiquetas por administração informada (`administracao_id`) e fornecer as opções de administração para o formulário. A seleção de uma administração ou de um estado SHALL atualizar automaticamente as congregações disponíveis por meio do envio do formulário, preservando os demais critérios válidos.

#### Scenario: Listagem de opções de administração na tela de etiquetas

- **GIVEN** que existem administrações e congregações cadastradas
- **WHEN** o usuário acessa a tela de etiquetas (`/labels`)
- **THEN** a view DEVE receber a lista de administrações e as congregações correspondentes

#### Scenario: Filtragem dinâmica de administração no select de etiquetas

- **GIVEN** a presença do select de administrações na tela de etiquetas
- **WHEN** o usuário digita no campo de busca de administração
- **THEN** as opções do select de administração DEVEM ser filtradas instantaneamente sem recarregar a página

#### Scenario: Seleção de administração atualiza as congregações

- **WHEN** o usuário escolhe uma administração no seletor
- **THEN** o formulário é submetido automaticamente para carregar congregações da administração selecionada

#### Scenario: Alteração de estado atualiza as congregações

- **WHEN** o usuário escolhe um estado no seletor
- **THEN** o formulário é submetido automaticamente para carregar congregações do estado selecionado

### Requirement: Filtro por dependência na tela de etiquetas

O sistema SHALL permitir filtrar os produtos marcados para impressão por dependência na tela de etiquetas (`GET /labels`). As opções de dependência e os produtos exibidos SHALL representar a dependência atual de cada produto: a dependência editada somente prevalece quando o produto está marcado como editado e a relação editada possui descrição exibível; caso contrário, a dependência original SHALL ser usada como fallback. A seleção ou limpeza do filtro de dependência SHALL submeter automaticamente o formulário, preservando a igreja selecionada.

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

#### Scenario: Alteração de dependência atualiza os códigos

- **WHEN** o usuário escolhe ou limpa uma dependência no filtro
- **THEN** o formulário é submetido automaticamente com a igreja selecionada e a lista de códigos é atualizada

### Requirement: Filtros colapsáveis no mobile para etiquetas

O sistema SHALL exibir, na tela `/labels`, o bloco de filtros dentro de um contêiner colapsável controlado por botão de alternância dedicado com o mesmo comportamento de produtos e relatórios. No mobile (`≤860px`) o contêiner inicia colapsado e no desktop (`≥861px`) sempre expandido, com o botão oculto no desktop. O rótulo SHALL refletir filtros ativos de etiquetas (`administracao_id`, `estado`, `comum_id`, `dependencia`).

#### Scenario: Mobile — filtros de etiquetas iniciam colapsados

- WHEN o usuário visita `/labels` em viewport ≤860px
- THEN o contêiner de filtros está colapsado e o botão exibe `aria-expanded="false"`

#### Scenario: Desktop — filtros visíveis e botão oculto

- WHEN o usuário visita `/labels` em viewport ≥861px
- THEN o contêiner está visível e o botão não é exibido

#### Scenario: Contagem de ativos no botão de etiquetas

- WHEN há filtros ativos em `/labels`
- THEN o botão indica "Filtros · N ativos"; sem filtros, apenas "Filtros"

#### Scenario: Controles permanecem funcionais quando expandido

- WHEN o contêiner de `/labels` está expandido
- THEN os controles de filtro permanecem funcionais

