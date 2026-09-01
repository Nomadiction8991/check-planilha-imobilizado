# relatorios-formulario Specification

## Purpose

Formulários 14.x e posição de estoque com filtros e exports por igreja, incluindo seleção eficiente com busca filtrável.

## ADDED Requirements

### Requirement: Busca filtrável de igreja na listagem de relatórios

O sistema SHALL exibir, na tela de listagem de relatórios (`GET /reports`), um campo de busca digitável associado ao seletor de igreja (`comum_id`) que filtra em tempo real as opções por correspondência case-insensitive no texto exibido (codigo - descricao), sem recarregar a página e sem alterar o conjunto retornado pelo servidor.

#### Scenario: Campo de busca está visível

- **WHEN** o usuário visita `/reports`
- **THEN** a página exibe um campo de busca digitável associado ao seletor de igreja, visível e rotulado de forma acessível

#### Scenario: Digitação filtra opções visíveis

- **WHEN** o usuário digita um termo que coincide com parte do texto de algumas igrejas
- **THEN** apenas as opções correspondentes permanecem visíveis/selecionáveis e as demais são ocultadas

#### Scenario: Limpar busca restaura todas as opções

- **WHEN** o usuário limpa o campo de busca
- **THEN** todas as igrejas voltam a ficar visíveis e o placeholder "Selecione" permanece disponível

#### Scenario: Nenhum resultado exibe mensagem e desabilita seletor

- **WHEN** o termo digitado não corresponde a nenhuma igreja
- **THEN** o sistema exibe mensagem indicando ausência de resultados e desabilita o seletor até que a busca seja ajustada

#### Scenario: Submissão preserva igreja filtrada

- **WHEN** o usuário seleciona uma igreja visível após filtrar e submete o filtro (GET com `comum_id`)
- **THEN** a listagem é filtrada pela igreja selecionada conforme comportamento existente

#### Scenario: Filtro é apenas de apresentação

- **WHEN** o usuário filtra no navegador
- **THEN** o conjunto de igrejas retornado pelo servidor permanece o mesmo; o filtro não altera query no backend
