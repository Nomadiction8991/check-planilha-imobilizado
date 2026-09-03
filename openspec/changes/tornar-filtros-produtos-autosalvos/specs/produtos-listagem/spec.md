## MODIFIED Requirements

### Requirement: Busca filtrável de igreja nos filtros de produtos

O sistema SHALL exibir, nas telas de listagem de produtos (`/products`) e verificação (`/products/verification`), um campo de busca digitável associado ao seletor de igreja (`comum_id`) que filtra em tempo real as opções por correspondência case-insensitive no texto exibido (codigo - descricao), sem recarregar a página, sem alterar o conjunto retornado pelo servidor e SHALL expose only churches belonging to administrations permitted to the current non-administrator user. Ao selecionar ou limpar qualquer filtro do formulário, a tela SHALL poder submeter automaticamente a consulta preservando os critérios já preenchidos; a submissão manual pelo botão existente SHALL continuar disponível.

#### Scenario: Campo de busca está visível nas duas telas

- WHEN o usuário visita `/products` ou `/products/verification`
- THEN a página exibe um campo de busca digitável associado ao seletor de igreja, visível e rotulado de forma acessível

#### Scenario: Opções respeitam o escopo do usuário

- GIVEN um usuário não administrador autorizado para a administração 10 e igrejas nas administrações 10 e 30
- WHEN ele abre uma das telas de produtos
- THEN o seletor de igreja exibe somente igrejas da administração 10

#### Scenario: Digitação filtra opções visíveis

- WHEN o usuário digita um termo que coincide com parte do texto de algumas igrejas
- THEN apenas as opções correspondentes permanecem visíveis/selecionáveis e as demais são ocultadas

#### Scenario: Limpar busca restaura todas as opções

- WHEN o usuário limpa o campo de busca da igreja
- THEN todas as igrejas permitidas voltam a ficar visíveis e o placeholder "Todas" permanece disponível

#### Scenario: Nenhum resultado exibe mensagem e desabilita seletor

- WHEN o termo digitado não corresponde a nenhuma igreja permitida
- THEN o sistema exibe mensagem indicando ausência de resultados e desabilita o seletor até que a busca seja ajustada

#### Scenario: Submissão preserva igreja filtrada

- WHEN o usuário seleciona uma igreja visível após filtrar e submete o filtro (GET com `comum_id`)
- THEN a listagem é filtrada pela igreja selecionada conforme comportamento existente

#### Scenario: Filtro é apenas de apresentação

- WHEN o usuário filtra no navegador
- THEN o conjunto de igrejas permitido retornado pelo servidor permanece o mesmo; o filtro não altera query no backend

#### Scenario: Select alterado atualiza a consulta automaticamente

- GIVEN o formulário de filtros está visível em uma tela de produtos
- WHEN o usuário altera um seletor de administração, igreja, estado, dependência, tipo ou status
- THEN o navegador submete uma única consulta GET após a alteração, mantendo os demais campos preenchidos

#### Scenario: Busca geral aguarda uma pausa antes de consultar

- GIVEN o usuário está digitando na busca geral
- WHEN ele para de digitar pelo intervalo configurado ou confirma a busca
- THEN o navegador submete a consulta GET com o texto informado sem submeter uma requisição a cada tecla

#### Scenario: Limpeza de busca atualiza a consulta

- GIVEN a busca geral contém texto e os demais filtros permanecem selecionados
- WHEN o usuário apaga o texto da busca geral
- THEN o navegador submete a consulta preservando os demais filtros e sem o parâmetro de busca

#### Scenario: Submissão manual continua disponível

- WHEN o usuário aciona o botão "Filtrar" ou utiliza o envio padrão do formulário
- THEN a consulta é submetida normalmente sem duplicar a requisição automática em andamento

#### Scenario: Feedback da atualização permanece reservado

- GIVEN o formulário foi renderizado antes de qualquer alteração
- WHEN o usuário altera um filtro que muda a consulta
- THEN a mensagem de atualização é exibida na região reservada de status, sem criar ou remover elementos durante o envio
