# produtos-listagem Specification

## Purpose
Filtros da listagem e verificação de produtos com seleção de igreja eficiente por busca filtrável client-side.
## Requirements
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

### Requirement: Classificação atual na listagem e verificação de produtos

O sistema SHALL exibir o tipo de bem e a dependência que representam a classificação atual do produto nas telas de listagem (`/products`), verificação (`/products/verificacao`) e edição (`/products/{product}/edit`). Para produtos editados, a classificação editada SHALL prevalecer quando a relação correspondente existir e tiver valor de exibição; na ausência de uma relação editada válida, o sistema SHALL usar a relação original disponível.

#### Scenario: Produto editado exibe classificação editada

- **GIVEN** um produto marcado como editado com tipo e dependência originais e com tipo e dependência editados válidos
- **WHEN** o usuário abre a tela de edição do produto
- **THEN** o bloco de valores atuais exibe o tipo e a dependência editados
- **AND** não exibe os valores originais como se fossem a classificação vigente

#### Scenario: Produto sem edição exibe classificação original

- **GIVEN** um produto não marcado como editado com tipo e dependência originais
- **WHEN** o usuário abre a tela de edição do produto
- **THEN** o bloco de valores atuais exibe o tipo e a dependência originais

#### Scenario: Edição sem relação válida usa fallback original

- **GIVEN** um produto marcado como editado cuja relação de tipo ou dependência editada não pode ser encontrada ou não possui valor de exibição
- **WHEN** o usuário abre a tela de edição do produto
- **THEN** o bloco de valores atuais exibe a relação original correspondente em vez de deixar a classificação vazia

#### Scenario: Listagem e verificação continuam exibindo a classificação atual

- **GIVEN** produtos editados e não editados com classificações originais e editadas
- **WHEN** o usuário abre a listagem ou a tela de verificação de produtos
- **THEN** cada produto exibe o tipo de bem e a dependência atuais conforme a mesma regra de prioridade e fallback

#### Scenario: Consulta carrega relações atuais sem consultas por linha

- **GIVEN** uma página de produtos com registros editados e não editados
- **WHEN** a listagem, a verificação ou a edição de um produto é renderizada
- **THEN** as relações originais e editadas necessárias para exibir a classificação são carregadas antes da renderização
- **AND** a quantidade de consultas não cresce uma vez por produto renderizado

### Requirement: Ações de produto respeitam a capacidade de edição

O sistema SHALL oferecer links para editar produtos na listagem e na verificação somente para administradores ou usuários autenticados que possuam a permissão `products.edit`. Usuários que possam consultar a tela, mas não tenham capacidade de edição, SHALL continuar vendo a identificação e os dados do produto, porém sem uma ação que conduza ao formulário de edição.

#### Scenario: Usuário com permissão pode editar pela listagem

- **GIVEN** um usuário autenticado com permissão `products.edit`
- **WHEN** ele abre a listagem de produtos
- **THEN** cada produto elegível exibe a ação para editar seu cadastro

#### Scenario: Usuário sem permissão vê a listagem em modo consulta

- **GIVEN** um usuário autenticado sem permissão `products.edit` que pode consultar produtos
- **WHEN** ele abre a listagem de produtos
- **THEN** os dados do produto permanecem visíveis e a ação de editar não é exibida

#### Scenario: Verificação oculta edição para usuário sem permissão

- **GIVEN** um usuário autenticado que acessa a verificação, mas não possui `products.edit`
- **WHEN** a tela de verificação é renderizada
- **THEN** a identificação, o checklist e as ações permitidas permanecem disponíveis, mas o link de edição não é exibido

#### Scenario: Administrador mantém a ação de edição

- **GIVEN** um administrador autenticado
- **WHEN** ele abre a listagem ou a verificação de produtos
- **THEN** a ação de editar permanece disponível

#### Scenario: Autorização do servidor permanece obrigatória

- **GIVEN** um usuário sem permissão que tenta acessar diretamente a rota de edição
- **WHEN** a requisição é processada
- **THEN** o servidor continua recusando o acesso conforme a autorização existente

### Requirement: Busca e filtros usam a classificação atual do produto

O sistema SHALL aplicar a busca geral e os filtros de dependência e tipo de bem sobre a classificação atual do produto. Para produtos editados, os valores editados válidos DEVEM ser considerados; quando não houver valor editado válido, a relação original DEVE ser usada como fallback. O sistema SHALL preservar o escopo de acesso vigente.

#### Scenario: Busca encontra produto pelo tipo editado

- **GIVEN** um produto editado com tipo original "CADEIRA" e tipo atual "MESA"
- **WHEN** o usuário consulta a busca geral pelo termo "MESA"
- **THEN** o produto é retornado

#### Scenario: Busca encontra produto pela dependência editada

- **GIVEN** um produto editado com dependência original "SALÃO" e dependência atual "SECRETARIA"
- **WHEN** o usuário consulta a busca geral pelo termo "SECRETARIA"
- **THEN** o produto é retornado

#### Scenario: Busca não usa classificação substituída como se fosse atual

- **GIVEN** um produto editado com tipo original "CADEIRA" e tipo atual "MESA"
- **WHEN** o usuário consulta a busca geral pelo termo "CADEIRA"
- **THEN** o produto não é retornado por causa do tipo original substituído

#### Scenario: Filtro de tipo usa o tipo editado

- **GIVEN** um produto editado com tipo original de identificador 4 e tipo atual de identificador 7
- **WHEN** o usuário filtra por tipo de bem 7
- **THEN** o produto é retornado

#### Scenario: Filtro de dependência usa a dependência editada

- **GIVEN** um produto editado com dependência original de identificador 2 e dependência atual de identificador 3
- **WHEN** o usuário filtra por dependência 3
- **THEN** o produto é retornado

#### Scenario: Filtros usam o original quando o valor editado é inválido

- **GIVEN** um produto editado com relação original válida e relação editada ausente ou sem valor de exibição
- **WHEN** o usuário consulta por essa classificação original
- **THEN** o produto é retornado usando a relação original como fallback

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

