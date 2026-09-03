## ADDED Requirements

### Requirement: Classificação atual na listagem e verificação de produtos

O sistema SHALL exibir o tipo de bem e a dependência que representam a classificação atual do produto nas telas de listagem (`/products`) e verificação (`/products/verificacao`). Para produtos editados, a classificação editada SHALL prevalecer quando a relação correspondente existir; na ausência de uma relação editada válida, o sistema SHALL usar a relação original disponível.

#### Scenario: Produto editado exibe classificação editada

- **GIVEN** um produto marcado como editado com tipo e dependência originais e com tipo e dependência editados válidos
- **WHEN** o usuário abre a listagem ou a verificação de produtos
- **THEN** o tipo e a dependência exibidos são os valores editados atuais

#### Scenario: Produto sem edição exibe classificação original

- **GIVEN** um produto não marcado como editado com tipo e dependência originais
- **WHEN** o usuário abre a listagem ou a verificação de produtos
- **THEN** o tipo e a dependência exibidos são os valores originais

#### Scenario: Edição sem relação válida usa fallback original

- **GIVEN** um produto marcado como editado cuja relação de tipo ou dependência editada não pode ser encontrada
- **WHEN** o usuário abre a listagem ou a verificação de produtos
- **THEN** a tela exibe a relação original correspondente em vez de deixar a classificação vazia

#### Scenario: Consulta carrega relações atuais sem consultas por linha

- **GIVEN** uma página de produtos com registros editados e não editados
- **WHEN** a paginação é executada
- **THEN** as relações originais e editadas necessárias para exibir a classificação são carregadas como parte da consulta da página
- **AND** a quantidade de consultas não cresce uma vez por produto renderizado
