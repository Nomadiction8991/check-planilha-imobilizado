# usuarios-listagem Specification

## Purpose
Filtros da listagem de usuários com seleção de administração eficiente por busca filtrável client-side.
## Requirements
### Requirement: Busca filtrável de administração nos filtros de usuários

O sistema SHALL exibir, na tela de listagem de usuários (`GET /users`), um campo de busca digitável associado ao seletor de administração (`administracao_id`) que filtra em tempo real as opções por correspondência case-insensitive no texto exibido (#id - descricao), sem recarregar a página e sem alterar o conjunto retornado pelo servidor.

#### Scenario: Campo de busca está visível

- **WHEN** o usuário visita `/users`
- **THEN** a página exibe um campo de busca digitável associado ao seletor de administração, visível e rotulado de forma acessível

#### Scenario: Digitação filtra opções visíveis

- **WHEN** o usuário digita um termo que coincide com parte do texto de algumas administrações
- **THEN** apenas as opções correspondentes permanecem visíveis/selecionáveis e as demais são ocultadas

#### Scenario: Limpar busca restaura todas as opções

- **WHEN** o usuário limpa o campo de busca
- **THEN** todas as administrações voltam a ficar visíveis e o placeholder "Todas" permanece disponível

#### Scenario: Nenhum resultado exibe mensagem e desabilita seletor

- **WHEN** o termo digitado não corresponde a nenhuma administração
- **THEN** o sistema exibe mensagem indicando ausência de resultados e desabilita o seletor até que a busca seja ajustada

#### Scenario: Submissão preserva administração filtrada

- **WHEN** o usuário seleciona uma administração visível após filtrar e submete o filtro (GET com `administracao_id`)
- **THEN** a listagem é filtrada pela administração selecionada conforme comportamento existente

#### Scenario: Filtro é apenas de apresentação

- **WHEN** o usuário filtra no navegador
- **THEN** o conjunto de administrações retornado pelo servidor permanece o mesmo; o filtro não altera query no backend

