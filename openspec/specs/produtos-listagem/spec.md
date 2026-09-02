# produtos-listagem Specification

## Purpose
Filtros da listagem e verificação de produtos com seleção de igreja eficiente por busca filtrável client-side.
## Requirements
### Requirement: Busca filtrável de igreja nos filtros de produtos

O sistema SHALL exibir, nas telas de listagem de produtos (`/products`) e verificação (`/products/verification`), um campo de busca digitável associado ao seletor de igreja (`comum_id`) que filtra em tempo real as opções por correspondência case-insensitive no texto exibido (codigo - descricao), sem recarregar a página e sem alterar o conjunto retornado pelo servidor.

#### Scenario: Campo de busca está visível nas duas telas

- **WHEN** o usuário visita `/products` ou `/products/verification`
- **THEN** a página exibe um campo de busca digitável associado ao seletor de igreja, visível e rotulado de forma acessível

#### Scenario: Digitação filtra opções visíveis

- **WHEN** o usuário digita um termo que coincide com parte do texto de algumas igrejas
- **THEN** apenas as opções correspondentes permanecem visíveis/selecionáveis e as demais são ocultadas

#### Scenario: Limpar busca restaura todas as opções

- **WHEN** o usuário limpa o campo de busca
- **THEN** todas as igrejas voltam a ficar visíveis e o placeholder "Todas" permanece disponível

#### Scenario: Nenhum resultado exibe mensagem e desabilita seletor

- **WHEN** o termo digitado não corresponde a nenhuma igreja
- **THEN** o sistema exibe mensagem indicando ausência de resultados e desabilita o seletor até que a busca seja ajustada

#### Scenario: Submissão preserva igreja filtrada

- **WHEN** o usuário seleciona uma igreja visível após filtrar e submete o filtro (GET com `comum_id`)
- **THEN** a listagem é filtrada pela igreja selecionada conforme comportamento existente

#### Scenario: Filtro é apenas de apresentação

- **WHEN** o usuário filtra no navegador
- **THEN** o conjunto de igrejas retornado pelo servidor permanece o mesmo; o filtro não altera query no backend

### Requirement: Filtragem por Administração na Listagem e Verificação de Produtos
The system SHALL allow filtering products by administration ID (`administracao_id`), matching products whose linked church belongs to the specified administration.

#### Scenario: Filtragem de produtos com ID de administração válido
- GIVEN produtos vinculados a igrejas de diferentes administrações
- WHEN a listagem (`/products`) ou tela de verificação (`/products/verification`) for consultada com o parâmetro `administracao_id`
- THEN apenas os produtos pertencentes a igrejas daquela administração DEVEM ser retornados na paginação

#### Scenario: Consulta sem filtro de administração
- GIVEN produtos cadastrados no sistema
- WHEN o parâmetro `administracao_id` não for informado ou for nulo/zero
- THEN todos os produtos ativos DEVEM ser retornados respeitando os demais filtros ativos

### Requirement: Busca Progressiva no Seletor de Administração nas Views de Produtos
The UI SHALL provide an interactive search input and select for administration filtering with instant client-side filtering and accessible feedback on `/products` and `/products/verification`.

#### Scenario: Filtragem interativa das opções de administração
- GIVEN a tela de listagem de produtos ou de verificação com o seletor de administrações
- WHEN o usuário digitar texto no campo de busca de administração
- THEN as opções não correspondentes DEVEM ser ocultadas e o contador/status acessível DEVE atualizar adequadamente

