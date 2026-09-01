# public-access Specification

## Purpose

Permite que usuários do fluxo público encontrem rapidamente sua igreja por meio de busca filtrável no formulário de acesso, mantendo compatibilidade com o envio e validação existentes.

## ADDED Requirements

### Requirement: Busca filtrável de igreja no acesso público

O sistema SHALL exibir um campo de busca digitável na tela de acesso público (`/assinatura-publica`) que filtra em tempo real as opções do seletor de igreja por correspondência case-insensitive no nome exibido, sem recarregar a página, preservando o envio normal do formulário com `comum_id`.

#### Scenario: Campo de busca está visível

- **WHEN** o usuário visita `/assinatura-publica`
- **THEN** a página exibe um campo de busca associado ao seletor de igreja, visível e rotulado de forma acessível

#### Scenario: Digitação filtra opções visíveis

- **WHEN** o usuário digita um termo no campo de busca que coincide com parte do nome de algumas igrejas
- **THEN** apenas as opções correspondentes permanecem visíveis/selecionáveis no seletor, e as demais são ocultadas

#### Scenario: Limpar busca restaura todas as opções

- **WHEN** o usuário limpa o campo de busca
- **THEN** todas as igrejas voltam a ficar visíveis no seletor e o placeholder "Selecione" permanece disponível

#### Scenario: Nenhum resultado exibe mensagem e desabilita envio

- **WHEN** o termo digitado não corresponde a nenhuma igreja
- **THEN** o sistema exibe mensagem indicando ausência de resultados e desabilita o seletor para evitar envio inválido até que a busca seja ajustada ou limpa

#### Scenario: Envio com igreja filtrada continua válido

- **WHEN** o usuário seleciona uma igreja visível após filtrar e envia o formulário
- **THEN** o sistema processa o envio com `comum_id` selecionado conforme fluxo existente (validação e redirecionamento sem mudança de rota ou parâmetros)

#### Scenario: Busca não altera opções no servidor

- **WHEN** o usuário aplica filtro no navegador
- **THEN** o conjunto de igrejas retornado pelo servidor permanece o mesmo; o filtro é apenas de apresentação no cliente
