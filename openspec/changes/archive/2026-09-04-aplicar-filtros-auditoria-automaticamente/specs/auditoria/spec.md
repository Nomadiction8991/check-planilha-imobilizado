## MODIFIED Requirements

### Requirement: Acesso à exportação pela tela de auditoria

A tela de auditoria SHALL oferecer um botão de exportação que dispara o download preservando os filtros atuais da consulta, SHALL responder com mensagem clara quando não houver eventos para exportar, SHALL rejeitar filtros de período inválidos antes de consultar eventos e SHALL atualizar a consulta automaticamente quando um filtro server-side for alterado.

#### Scenario: Período inválido na consulta

- **GIVEN** um usuário com permissão de ver auditoria autenticado
- **WHEN** ele informa data inicial ou final em formato inválido
- **THEN** o sistema retorna à tela de auditoria sem consultar eventos
- **AND** exibe mensagem indicando qual data precisa ser corrigida
- **AND** preserva filtros informados na tela

#### Scenario: Data final anterior à data inicial

- **GIVEN** um usuário com permissão de ver auditoria autenticado
- **WHEN** ele informa data final anterior à data inicial
- **THEN** o sistema retorna à tela de auditoria sem consultar eventos
- **AND** exibe mensagem informando que período está invertido
- **AND** preserva filtros informados na tela

#### Scenario: Botão preserva filtros

- **GIVEN** um usuário na tela de auditoria com filtros preenchidos
- **WHEN** ele aciona o botão de exportar
- **THEN** a requisição leva consigo os mesmos parâmetros de filtro exibidos na tela

#### Scenario: Exportação com período válido

- **GIVEN** filtros de período válidos
- **WHEN** o usuário aciona o botão de exportar
- **THEN** a requisição leva consigo os mesmos parâmetros de filtro exibidos na tela

#### Scenario: Sem eventos para exportar

- **GIVEN** filtros que não casam com nenhum evento auditado
- **WHEN** o usuário solicita a exportação
- **THEN** o sistema redireciona de volta à tela de auditoria mantendo os filtros
- **AND** exibe mensagem informando que não há eventos para os filtros atuais

#### Scenario: Alteração de filtro atualiza a consulta

- **GIVEN** um usuário autenticado na tela de auditoria
- **WHEN** ele altera a administração, o módulo ou uma das datas do período
- **THEN** o sistema envia automaticamente a consulta com os valores atuais dos filtros
- **AND** exibe um estado acessível indicando que a auditoria está sendo atualizada

#### Scenario: Digitação da busca geral aguarda pausa

- **GIVEN** um usuário autenticado na tela de auditoria
- **WHEN** ele digita na busca geral
- **THEN** o sistema aguarda uma breve pausa antes de enviar a consulta
- **AND** uma digitação contínua não dispara uma navegação para cada caractere

#### Scenario: Submissão manual continua disponível

- **GIVEN** a tela de auditoria com os filtros preenchidos
- **WHEN** o usuário aciona o botão de filtragem
- **THEN** a consulta é enviada normalmente
- **AND** a aplicação não envia uma segunda consulta automática para os mesmos valores
