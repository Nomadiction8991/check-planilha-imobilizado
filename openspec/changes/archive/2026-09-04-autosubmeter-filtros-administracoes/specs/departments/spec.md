## ADDED Requirements

### Requirement: Atualização automática dos filtros de dependências
A tela de dependências SHALL enviar automaticamente uma nova consulta quando um filtro server-side for alterado, SHALL aguardar uma breve pausa durante a digitação da busca textual e SHALL preservar as buscas auxiliares como comportamento local.

#### Scenario: Alteração de filtro atualiza a listagem
- **GIVEN** a pessoa está na listagem de dependências
- **WHEN** ela altera administração, igreja, estado ou descrição
- **THEN** a tela envia automaticamente a consulta com os valores atuais
- **AND** reinicia a consulta na primeira página

#### Scenario: Buscas auxiliares permanecem locais
- **GIVEN** existem buscas auxiliares para administração e igreja
- **WHEN** a pessoa digita em uma dessas buscas
- **THEN** as opções correspondentes são filtradas instantaneamente no navegador
- **AND** nenhuma busca auxiliar é enviada ao servidor

#### Scenario: Digitação da descrição aguarda pausa
- **GIVEN** a pessoa está digitando uma descrição
- **WHEN** ela continua digitando sem pausa
- **THEN** a tela aguarda uma breve pausa antes de consultar

#### Scenario: Filtro manual continua disponível
- **GIVEN** a pessoa deseja confirmar os filtros pelo botão
- **WHEN** ela aciona “Filtrar”
- **THEN** a consulta é enviada normalmente sem uma submissão automática duplicada
