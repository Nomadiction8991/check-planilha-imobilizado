## Purpose

Mantém os arquivos temporários de análise disponíveis até que a importação esteja comprovadamente encerrada, evitando perda de dados causada por estados novos ou inválidos.

## ADDED Requirements

### Requirement: Limpeza somente em estados seguros

O sistema SHALL remover automaticamente um arquivo de análise quando a importação correspondente não existir ou estiver em estado terminal conhecido (`concluida` ou `erro`). O sistema MUST preservar o arquivo quando a importação estiver `aguardando`, `processando`, vazia ou em qualquer estado não reconhecido.

#### Scenario: Importação concluída pode ser removida
- **GIVEN** existe um arquivo de análise associado a uma importação com estado `concluida`
- **WHEN** a rotina de limpeza é executada sem simulação
- **THEN** o arquivo de análise é removido

#### Scenario: Importação com erro pode ser removida
- **GIVEN** existe um arquivo de análise associado a uma importação com estado `erro`
- **WHEN** a rotina de limpeza é executada sem simulação
- **THEN** o arquivo de análise é removido

#### Scenario: Arquivo sem importação pode ser removido
- **GIVEN** existe um arquivo de análise cujo identificador não possui registro de importação
- **WHEN** a rotina de limpeza é executada sem simulação
- **THEN** o arquivo de análise é removido sem apagar registros do banco

#### Scenario: Importação ativa é preservada
- **GIVEN** existe um arquivo de análise associado a uma importação com estado `aguardando` ou `processando`
- **WHEN** a rotina de limpeza é executada
- **THEN** o arquivo de análise permanece disponível

#### Scenario: Estado desconhecido é preservado
- **GIVEN** existe um arquivo de análise associado a uma importação com estado vazio ou não reconhecido
- **WHEN** a rotina de limpeza é executada
- **THEN** o arquivo de análise permanece disponível e a rotina informa que ele foi mantido

### Requirement: Simulação e remoção de registros respeitam a classificação segura

O sistema SHALL listar na simulação quais arquivos seriam removidos sem alterar arquivos ou registros. Quando solicitada a remoção de registros, o sistema MUST apagar registros somente dos arquivos classificados como removíveis e MUST preservar registros associados a estados ativos ou desconhecidos.

#### Scenario: Simulação preserva um estado desconhecido
- **GIVEN** existe um arquivo de análise associado a uma importação com estado não reconhecido
- **WHEN** a rotina é executada em modo de simulação
- **THEN** o arquivo e seu registro de importação permanecem intactos
- **AND** a saída informa que o arquivo foi mantido

#### Scenario: Remoção forçada não apaga estado desconhecido
- **GIVEN** existe um arquivo de análise associado a uma importação com estado não reconhecido
- **WHEN** a rotina é executada solicitando também a remoção dos registros
- **THEN** o arquivo e os registros associados permanecem intactos
