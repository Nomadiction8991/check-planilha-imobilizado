## MODIFIED Requirements

### Requirement: Exportação CSV da auditoria

O sistema SHALL permitir exportar, em um único arquivo CSV, TODOS os eventos auditados que casam com os filtros atuais (busca geral, módulo e período), respeitando o escopo de visualização do usuário autenticado — sem o limite de paginação aplicado na tela. Campos textuais controlados por usuários SHALL ser neutralizados contra interpretação como fórmula por planilhas.

#### Scenario: Exportação com filtros aplicados

- **WHEN** usuário com permissão de ver auditoria autenticado solicita a exportação com busca "Login" e módulo "Sessão" informados
- **THEN** download contém todas as entradas que casam com os filtros (não apenas a página atual)
- **AND** arquivo usa separador ponto e vírgula, BOM UTF-8 e cabeçalho descritivo das colunas

#### Scenario: Colunas do CSV

- **WHEN** conteúdo exportado é lido com separador ponto e vírgula
- **THEN** linhas trazem data/hora, usuário, e-mail, administração, igreja, módulo, ação, descrição, rota, caminho, método, código HTTP e IP
- **AND** valores ausentes são exportados como célula vazia, nunca como texto de erro

#### Scenario: Escopo respeitado

- **WHEN** usuário não administrador vinculado a uma administração específica solicita a exportação
- **THEN** apenas eventos dentro do escopo dele são incluídos no arquivo

#### Scenario: Exportação filtrada protege conteúdo textual

- **WHEN** usuário autorizado exporta auditoria com filtros aplicados e evento contendo texto iniciado por caractere de fórmula
- **THEN** download contém somente eventos compatíveis e prefixa texto perigoso com apóstrofo

#### Scenario: Exportação sem resultados mantém comportamento atual

- **WHEN** filtros não encontram eventos no escopo do usuário
- **THEN** sistema não gera arquivo vazio e informa que não há eventos para os filtros atuais
