# departments Specification

## Purpose
TBD - created by archiving change filtro-dependencias-busca-administracao. Update Purpose after archive.
## Requirements
### Requirement: Filtragem por Administração em Dependências
The system SHALL allow filtering departments by administration ID (`administracao_id`), matching departments whose linked church belongs to the specified administration.

#### Scenario: Filtragem de dependências com ID de administração válido
- GIVEN dependências vinculadas a igrejas de diferentes administrações
- WHEN a listagem de dependências for consultada com o parâmetro `administracao_id`
- THEN apenas as dependências pertencentes a igrejas daquela administração DEVEM ser retornadas na paginação

#### Scenario: Listagem sem filtro de administração
- GIVEN dependências cadastradas no sistema
- WHEN o parâmetro `administracao_id` não for informado ou for nulo/zero
- THEN todas as dependências DEVEM ser retornadas respeitando os demais filtros ativos

### Requirement: Busca Progressiva no Seletor de Administração na View de Dependências
The UI SHALL provide an interactive search input and select for administration filtering with instant client-side filtering and accessible feedback.

#### Scenario: Filtragem interativa das opções de administração
- GIVEN a tela de listagem de dependências com o seletor de administrações
- WHEN o usuário digitar texto no campo de busca de administração
- THEN as opções não correspondentes DEVEM ser ocultadas e o contador/status acessível DEVE atualizar adequadamente

### Requirement: Filtrar dependências por estado (UF) da igreja
O sistema SHALL permitir filtrar dependências pela Unidade Federativa (UF) da igreja vinculada na consulta de dependências.

#### Scenario: Filtragem por estado existente
- GIVEN dependências cadastradas vinculadas a igrejas em 'SP' e 'RJ'
- WHEN o usuário consulta a listagem de dependências filtrando por estado 'SP'
- THEN apenas as dependências vinculadas a igrejas de 'SP' devem ser retornadas

#### Scenario: Estado não informado
- GIVEN dependências de múltiplos estados
- WHEN o usuário consulta a listagem sem especificar o estado
- THEN todas as dependências devem ser retornadas respeitando os demais filtros

### Requirement: Restrição de escopo na escrita de dependências

Ao criar, atualizar ou excluir dependência, o sistema SHALL exigir que toda igreja envolvida (atual da dependência existente e nova igreja informada) esteja dentro do escopo do usuário restrito; fora do escopo a operação SHALL ser rejeitada com mensagem `A igreja selecionada está fora do seu escopo permitido.` sem mutação.

#### Scenario: Criação de dependência fora do escopo é rejeitada

- WHEN a igreja informada para nova dependência está fora do escopo
- THEN a criação SHALL ser rejeitada com status de erro e nenhum registro é criado

#### Scenario: Atualização para igreja fora do escopo é rejeitada

- WHEN a nova igreja informada na atualização está fora do escopo
- THEN a atualização SHALL ser rejeitada com status de erro e o registro permanece inalterado

#### Scenario: Atualização ou exclusão de dependência fora do escopo atual é rejeitada

- WHEN a dependência alvo pertence a igreja fora do escopo
- THEN qualquer tentativa de alteração ou exclusão SHALL ser rejeitada com status de erro

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

