# produtos-listagem Specification

## Purpose

Filtros da listagem e verificação de produtos com seleção de igreja eficiente por busca filtrável client-side.

## ADDED Requirements

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
