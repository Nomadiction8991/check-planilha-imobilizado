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

### Requirement: Acesso à exportação pela tela de auditoria
A tela de auditoria SHALL oferecer um botão de exportação que dispara o download preservando os filtros atuais da consulta, e SHALL responder com mensagem clara quando não houver eventos para exportar.

#### Scenario: Botão preserva filtros
- GIVEN um usuário na tela de auditoria com filtros preenchidos
- WHEN ele aciona o botão de exportar
- THEN a requisição leva consigo os mesmos parâmetros de filtro exibidos na tela

#### Scenario: Sem eventos para exportar
- GIVEN filtros que não casam com nenhum evento auditado
- WHEN o usuário solicita a exportação
- THEN o sistema redireciona de volta à tela de auditoria mantendo os filtros
- AND exibe mensagem informando que não há eventos para os filtros atuais

