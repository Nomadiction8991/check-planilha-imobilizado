# etiquetas-listagem Specification

## Purpose
Filtros da tela de etiquetas com seleção de igreja eficiente por busca filtrável client-side.
## Requirements
### Requirement: Busca filtrável de igreja nos filtros de etiquetas

O sistema SHALL exibir, na tela de etiquetas (`GET /labels`), um campo de busca digitável associado ao seletor de igreja (`comum_id`) que filtra em tempo real as opções por correspondência case-insensitive no texto exibido (codigo - descricao), sem recarregar a página e sem alterar o conjunto retornado pelo servidor.

#### Scenario: Campo de busca está visível

- **WHEN** o usuário visita `/labels`
- **THEN** a página exibe um campo de busca digitável associado ao seletor de igreja, visível e rotulado de forma acessível

#### Scenario: Digitação filtra opções visíveis

- **WHEN** o usuário digita um termo que coincide com parte do texto de algumas igrejas
- **THEN** apenas as opções correspondentes permanecem visíveis/selecionáveis e as demais são ocultadas

#### Scenario: Limpar busca restaura todas as opções

- **WHEN** o usuário limpa o campo de busca
- **THEN** todas as igrejas voltam a ficar visíveis e o placeholder "Selecione uma igreja" permanece disponível

#### Scenario: Nenhum resultado exibe mensagem e desabilita seletor

- **WHEN** o termo digitado não corresponde a nenhuma igreja
- **THEN** o sistema exibe mensagem acessível indicando ausência de resultados e desabilita temporariamente o seletor até que a busca seja ajustada

### Requirement: Filtro por administração na seleção de congregações para etiquetas
O sistema SHALL permitir filtrar as congregações disponíveis na tela de etiquetas por administração informada (`administracao_id`) e fornecer as opções de administração para o formulário.

#### Scenario: Listagem de opções de administração na tela de etiquetas
- GIVEN que existem administrações e congregações cadastradas
- WHEN o usuário acessa a tela de etiquetas (`/labels`)
- THEN a view DEVE receber a lista de administrações e as congregações correspondentes

#### Scenario: Filtragem dinâmica de administração no select de etiquetas
- GIVEN a presença do select de administrações na tela de etiquetas
- WHEN o usuário digita no campo de busca de administração
- THEN as opções do select de administração DEVEM ser filtradas instantaneamente sem recarregar a página

