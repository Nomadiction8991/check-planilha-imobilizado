## MODIFIED Requirements

### Requirement: Busca Progressiva no Seletor de Administração nas Views de Produtos

The UI SHALL provide an interactive search input and select for administration filtering with instant client-side filtering and accessible feedback on `/products` and `/products/verification`. When the search is cleared, all permitted administration options SHALL be restored and any selected administration hidden by the search SHALL be cleared before an automatic filter submission.

#### Scenario: Filtragem interativa das opções de administração

- GIVEN a tela de listagem de produtos ou de verificação com o seletor de administrações
- WHEN o usuário digitar texto no campo de busca de administração
- THEN as opções não correspondentes DEVEM ser ocultadas e o contador/status acessível DEVE atualizar adequadamente

#### Scenario: Limpeza da busca de administração remove seleção incompatível

- GIVEN uma administração selecionada foi ocultada por uma busca local
- WHEN o usuário limpa a busca de administração
- THEN todas as administrações permitidas voltam a ficar disponíveis, a seleção é removida e a listagem é atualizada sem essa administração

### Requirement: Busca filtrável de igreja nos filtros de produtos

O sistema SHALL exibir, nas telas de listagem de produtos (`/products`) e verificação (`/products/verification`), um campo de busca digitável associado ao seletor de igreja (`comum_id`) que filtra em tempo real as opções por correspondência case-insensitive no texto exibido (codigo - descricao), sem recarregar a página, sem alterar o conjunto retornado pelo servidor e SHALL expose only churches belonging to administrations permitted to the current non-administrator user. When the search is cleared, all permitted church options SHALL be restored and any selected church hidden by the search SHALL be cleared before an automatic filter submission.

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

- GIVEN uma igreja selecionada foi ocultada por uma busca local
- WHEN o usuário limpa a busca de igreja
- THEN todas as igrejas permitidas voltam a ficar disponíveis, a seleção é removida e a listagem é atualizada sem essa igreja

#### Scenario: Nenhum resultado exibe mensagem e desabilita seletor

- WHEN o termo digitado não corresponde a nenhuma igreja permitida
- THEN o sistema exibe mensagem indicando ausência de resultados e desabilita o seletor até que a busca seja ajustada

#### Scenario: Submissão preserva igreja filtrada

- WHEN o usuário seleciona uma igreja visível após filtrar e submete o filtro (GET com `comum_id`)
- THEN a listagem é filtrada pela igreja selecionada conforme comportamento existente

#### Scenario: Filtro é apenas de apresentação

- WHEN o usuário filtra no navegador
- THEN o conjunto de igrejas permitido retornado pelo servidor permanece o mesmo; o filtro não altera query no backend
