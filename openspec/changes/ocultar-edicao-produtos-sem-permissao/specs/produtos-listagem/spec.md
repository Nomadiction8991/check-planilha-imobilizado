## MODIFIED Requirements

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
