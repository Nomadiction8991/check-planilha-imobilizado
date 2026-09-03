## MODIFIED Requirements

### Requirement: Filtragem por Administração na Listagem e Verificação de Produtos
O sistema SHALL allow filtering products by administration ID (`administracao_id`), matching products whose linked church belongs to the specified administration, and SHALL limit non-administrator users to products belonging to one of their permitted administrations even when no administration filter is supplied.

#### Scenario: Filtragem de produtos com ID de administração válido
- GIVEN produtos vinculados a igrejas de diferentes administrações
- WHEN a listagem (`/products`) ou tela de verificação (`/products/verification`) for consultada com o parâmetro `administracao_id`
- THEN apenas os produtos pertencentes à administração informada DEVEM ser retornados na paginação

#### Scenario: Consulta sem filtro de administração
- GIVEN produtos cadastrados no sistema
- WHEN o parâmetro `administracao_id` não for informado ou for nulo/zero
- THEN todos os produtos ativos DEVEM ser retornados respeitando os demais filtros ativos

#### Scenario: Consulta sem filtro de administração para usuário restrito
- GIVEN um usuário não administrador autorizado para as administrações 10 e 20 e produtos em administrações 10, 20 e 30
- WHEN o parâmetro `administracao_id` não for informado ou for nulo/zero
- THEN somente produtos das administrações 10 e 20 DEVEM ser retornados respeitando os demais filtros ativos

#### Scenario: Filtro solicitado fora das permissões do usuário
- GIVEN um usuário não administrador autorizado somente para a administração 10
- WHEN ele consulta a listagem com `administracao_id=30`
- THEN nenhum produto da administração 30 DEVEM ser retornado

#### Scenario: Administrador consulta sem filtro
- GIVEN um usuário administrador e produtos vinculados a várias administrações
- WHEN ele consulta a listagem sem `administracao_id`
- THEN produtos de todas as administrações DEVEM permanecer disponíveis

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
