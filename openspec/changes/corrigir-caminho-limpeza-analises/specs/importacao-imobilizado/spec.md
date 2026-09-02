## MODIFIED Requirements

### Requirement: Persistência e limpeza de análises temporárias
O sistema SHALL armazenar e localizar os arquivos intermediários de análise no mesmo diretório configurado para a aplicação, usando o padrão `storage/tmp` quando nenhuma configuração alternativa for fornecida. A limpeza SHALL identificar arquivos de análise órfãos nesse diretório sem remover arquivos de importações ativas.

#### Scenario: Limpeza usa o diretório padrão da aplicação
- **GIVEN** que existe um arquivo de análise associado a uma importação concluída no diretório temporário padrão da aplicação
- **WHEN** a rotina de limpeza é executada sem diretório alternativo
- **THEN** o arquivo órfão é identificado e removido

#### Scenario: Importação ativa permanece protegida
- **GIVEN** que existe um arquivo de análise associado a uma importação em andamento no diretório temporário padrão
- **WHEN** a rotina de limpeza é executada
- **THEN** o arquivo permanece disponível para o processamento

#### Scenario: Diretório alternativo é respeitado
- **GIVEN** que a rotina recebe um diretório alternativo para execução controlada
- **WHEN** a rotina procura análises órfãs
- **THEN** somente os arquivos do diretório alternativo são considerados
