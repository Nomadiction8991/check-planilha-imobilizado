## MODIFIED Requirements

### Requirement: Busca filtrável de igreja nos filtros de produtos
O sistema SHALL exibir, nas telas de listagem de produtos (`/products`) e verificação (`/products/verification`), um campo de busca digitável associado ao seletor de igreja (`comum_id`) que filtra em tempo real as opções por correspondência case-insensitive no texto exibido (codigo - descricao), sem recarregar a página, sem alterar o conjunto retornado pelo servidor e SHALL expose only churches belonging to administrations permitted to the current non-administrator user.

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
- WHEN o usuário limpa o campo de busca
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

## ADDED Requirements

### Requirement: Remoção de filtro ativo reinicia a paginação

A listagem e a verificação de produtos SHALL remove the current page parameter when the user removes an active filter or clears all filters, so the resulting view starts at the first page while preserving the remaining filter criteria.

#### Scenario: Remover um filtro em uma página posterior

- GIVEN o usuário está na página 3 com filtros ativos e seleciona o controle para remover um deles
- WHEN a nova URL é construída
- THEN o filtro escolhido é removido, os demais parâmetros são preservados e o parâmetro de página não é enviado

#### Scenario: Limpar todos os filtros em uma página posterior

- GIVEN o usuário está na página 2 com filtros ativos
- WHEN ele aciona "Limpar todos"
- THEN a nova URL mantém somente o caminho da tela, sem filtros nem parâmetro de página

#### Scenario: Remover filtro sem página informada

- GIVEN o usuário está na primeira página ou não possui parâmetro de página na URL
- WHEN remove um filtro ativo
- THEN a navegação continua funcionando sem adicionar um parâmetro de página vazio ou inválido
