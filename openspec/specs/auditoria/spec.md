# auditoria Specification

## Purpose
TBD - created by archiving change exportar-auditoria-csv. Update Purpose after archive.
## Requirements
### Requirement: Exportação CSV da auditoria
O sistema SHALL permitir exportar, em um único arquivo CSV, TODOS os eventos auditados que casam com os filtros atuais (busca geral, módulo e período), respeitando o escopo de visualização do usuário autenticado — sem o limite de paginação aplicado na tela.

#### Scenario: Exportação com filtros aplicados
- GIVEN um usuário com permissão de ver auditoria autenticado
- WHEN ele solicita a exportação com busca "Login" e módulo "Sessão" informados
- THEN o download contém todas as entradas que casam com os filtros (não apenas a página atual)
- AND o arquivo usa separador ponto e vírgula, BOM UTF-8 e cabeçalho descritivo das colunas

#### Scenario: Colunas do CSV
- GIVEN qualquer exportação gerada
- WHEN o conteúdo é lido com separador ponto e vírgula
- THEN as linhas trazem data/hora, usuário, e-mail, administração, igreja, módulo, ação, descrição, rota, caminho, método, código HTTP e IP
- AND valores ausentes são exportados como célula vazia, nunca como texto de erro

#### Scenario: Escopo respeitado
- GIVEN um usuário não administrador vinculado a uma administração específica
- WHEN ele solicita a exportação
- THEN apenas eventos dentro do escopo dele são incluídos no arquivo

#### Scenario: Exportação filtrada protege conteúdo textual
- **WHEN** usuário autorizado exporta auditoria com filtros aplicados e evento contendo texto iniciado por caractere de fórmula
- **THEN** download contém somente eventos compatíveis e prefixa texto perigoso com apóstrofo

#### Scenario: Exportação sem resultados mantém comportamento atual
- **WHEN** filtros não encontram eventos no escopo do usuário
- **THEN** sistema não gera arquivo vazio e informa que não há eventos para os filtros atuais

### Requirement: Acesso à exportação pela tela de auditoria
A tela de auditoria SHALL oferecer um botão de exportação que dispara o download preservando os filtros atuais da consulta, SHALL responder com mensagem clara quando não houver eventos para exportar e SHALL rejeitar filtros de período inválidos antes de consultar eventos.

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

### Requirement: Filtros de período da auditoria
O sistema SHALL aceitar filtros de período somente quando cada data estiver no formato AAAA-MM-DD e representar uma data válida do calendário. Quando ambas forem informadas, a data final SHALL ser igual ou posterior à data inicial. Ao rejeitar o filtro, o sistema SHALL retornar à tela de auditoria preservando os demais filtros informados e SHALL explicar o erro no campo correspondente.

#### Scenario: Data de calendário inexistente
- GIVEN que usuário informa `data_inicio=2026-02-31`
- WHEN usuário consulta auditoria
- THEN sistema rejeita filtro e informa que data inicial precisa usar uma data válida
- AND sistema preserva filtros enviados no redirecionamento

#### Scenario: Data final anterior à inicial
- GIVEN que usuário informa data inicial posterior à data final
- WHEN usuário consulta ou exporta auditoria
- THEN sistema rejeita filtro e informa que data final não pode ser anterior à inicial
- AND sistema preserva filtros enviados no redirecionamento

#### Scenario: Período válido
- GIVEN que usuário informa duas datas válidas em ordem cronológica
- WHEN usuário consulta auditoria
- THEN sistema aplica período informado sem erro

