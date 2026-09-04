# relatorios-listagem Specification

## Purpose
TBD - created by archiving change filtro-relatorios-busca-administracao. Update Purpose after archive.
## Requirements
### Requirement: Filtro por administração na seleção de relatórios

O sistema SHALL permitir filtrar a lista de congregações disponíveis na tela de relatórios por administração informada (`administracao_id`), além de fornecer as opções de administração para o formulário. Ao alterar administração, estado ou igreja no formulário, a tela SHALL submeter automaticamente uma única consulta GET preservando os demais critérios; a submissão manual pelo botão existente SHALL continuar disponível.

#### Scenario: Listagem de opções de administrações
- GIVEN que existem administrações cadastradas no banco
- WHEN o usuário acessa a tela de relatórios (`/reports`)
- THEN a view DEVE receber a lista de administrações ordenadas por descrição para permitir o filtro

#### Scenario: Filtragem dinâmica de administrações no select
- GIVEN a presença do select de administrações na tela de relatórios
- WHEN o usuário digita no campo de busca de administração
- THEN as opções do select de administração DEVEM ser filtradas instantaneamente sem recarregar a página

#### Scenario: Select alterado atualiza a consulta automaticamente
- GIVEN o formulário de filtros está visível na tela de relatórios
- WHEN o usuário altera o seletor de administração, estado ou igreja
- THEN o navegador submete uma única consulta GET após a alteração, mantendo os demais campos preenchidos

#### Scenario: Submissão manual continua disponível
- WHEN o usuário aciona o botão "Carregar relatórios" ou utiliza o envio padrão do formulário
- THEN a consulta é submetida normalmente sem duplicar a requisição automática em andamento

#### Scenario: Feedback da atualização permanece reservado
- GIVEN o formulário foi renderizado antes de qualquer alteração
- WHEN o usuário altera um filtro que muda a consulta
- THEN a mensagem de atualização é exibida na região reservada de status, sem criar ou remover elementos durante o envio

### Requirement: [ADDED] Filtrar opções de igrejas por estado na listagem de relatórios
O sistema SHALL permitir que o usuário filtre a lista de igrejas pelo estado (UF) no seletor de relatórios.

#### Scenario: Filtrando igrejas por estado
- GIVEN um usuário autenticado na tela de relatórios
- WHEN o usuário seleciona uma UF (ex: "MT") no filtro de estado
- THEN o seletor de igrejas exibe apenas as igrejas correspondentes ao estado informado
- AND a seleção de estado é preservada na interface

#### Scenario: Sem filtro de estado
- GIVEN um usuário na tela de relatórios sem filtro de estado
- WHEN o usuário carrega a página
- THEN todas as igrejas disponíveis são exibidas no seletor

### Requirement: Atualização automática dos filtros de relatórios

A tela de relatórios SHALL atualizar os resultados automaticamente quando um filtro enviado pelo servidor for alterado, sem submeter os campos de busca que servem apenas para filtrar opções localmente. A atualização SHALL manter os parâmetros atuais do formulário e evitar nova navegação quando a assinatura dos valores não mudar.

#### Scenario: Busca local não dispara consulta
- GIVEN o usuário está digitando no campo de busca de administração ou igreja
- WHEN o texto muda para filtrar as opções exibidas
- THEN a página filtra as opções localmente sem submeter o formulário

#### Scenario: Busca local restaura opções sem alterar resultados
- GIVEN o usuário limpou o campo de busca de administração ou igreja
- WHEN o controle local é atualizado
- THEN as opções permitidas voltam a ser exibidas e nenhuma consulta ao servidor é criada apenas pela limpeza

### Requirement: Filtros colapsáveis no mobile para relatórios e etiquetas

O sistema SHALL exibir, nas telas `/reports` e `/labels`, o bloco de filtros dentro de um contêiner colapsável controlado por botão de alternância dedicado, seguindo o mesmo padrão visual já usado em `/products`. No mobile (largura de viewport `≤860px`), o contêiner SHALL iniciar colapsado (filtros não visíveis) por padrão e SHALL expandir/recolher ao acionar o botão. No desktop (`≥861px`), o contêiner SHALL permanecer sempre expandido e o botão SHALL estar oculto. O rótulo do botão SHALL refletir a contagem de filtros ativos da tela; quando nenhum filtro estiver ativo o rótulo SHALL ser apenas "Filtros".

#### Scenario: Mobile — filtros de relatórios colapsados por padrão

- WHEN o usuário visita `/reports` em viewport ≤860px sem interação prévia
- THEN o contêiner de filtros está colapsado (não visível) e o botão "Filtros" está visível com `aria-expanded="false"`

#### Scenario: Mobile — expandir filtros de relatórios

- WHEN o usuário aciona o botão de alternância de filtros em `/reports` no mobile
- THEN o contêiner de filtros torna-se visível e o botão passa a `aria-expanded="true"`

#### Scenario: Mobile — recolher filtros de relatórios

- WHEN o contêiner de relatórios está expandido no mobile e o usuário aciona novamente o botão
- THEN o contêiner volta ao estado colapsado e `aria-expanded` retorna a `false`

#### Scenario: Mobile — filtros de etiquetas colapsados por padrão

- WHEN o usuário visita `/labels` em viewport ≤860px sem interação prévia
- THEN o contêiner de filtros de etiquetas está colapsado e o botão "Filtros" está visível com `aria-expanded="false"`

#### Scenario: Mobile — expandir e recolher etiquetas

- WHEN o usuário aciona o botão de alternância de filtros em `/labels` no mobile
- THEN o contêiner alterna entre visível (`aria-expanded="true"`) e colapsado (`aria-expanded="false"`)

#### Scenario: Desktop — filtros sempre visíveis e botão oculto em relatórios e etiquetas

- WHEN o usuário visita `/reports` ou `/labels` em viewport ≥861px
- THEN o contêiner de filtros está visível independentemente do estado mobile e o botão de alternância não é exibido

#### Scenario: Botão de relatórios reflete contagem de filtros ativos

- WHEN há critérios ativos em `/reports` (ex.: `administracao_id`, `estado`, `comum_id`)
- THEN o rótulo do botão inclui a contagem (ex.: "Filtros · 2 ativos"); quando nenhum filtro está ativo, o rótulo é apenas "Filtros"

#### Scenario: Botão de etiquetas reflete contagem de filtros ativos

- WHEN há critérios ativos em `/labels` (ex.: `administracao_id`, `estado`, `comum_id`, `dependencia`)
- THEN o rótulo do botão inclui a contagem de filtros ativos da tela de etiquetas

#### Scenario: Filtragem e submissão preservadas com container colapsável

- WHEN o usuário aplica filtros em `/reports` ou `/labels` com o contêiner colapsado ou expandido
- THEN os parâmetros de filtro continuam sendo enviados e a submissão automática existente continua funcionando sem depender do estado visual do contêiner

#### Scenario: Compatibilidade com controles existentes

- WHEN o contêiner de `/reports` ou `/labels` está expandido
- THEN todos os controles existentes (busca de administração/igreja, UF, dependência, botões Filtrar/Limpar) permanecem funcionais e acessíveis dentro do contêiner

### Requirement: Seleção de igreja compatível com os filtros de relatórios

A tela de relatórios SHALL consider a church selection valid only when the church belongs to the options returned for the active administration and state filters. If the selected church is outside those options, the system MUST clear the effective selection and MUST NOT load reports for that church.

#### Scenario: Igreja selecionada permanece válida

- **GIVEN** a igreja selecionada pertence à administração e ao estado atualmente filtrados
- **WHEN** o usuário acessa a tela de relatórios
- **THEN** a igreja permanece selecionada e os relatórios disponíveis são carregados para ela

#### Scenario: Alteração de filtro invalida a igreja selecionada

- **GIVEN** a igreja informada na consulta não pertence às opções filtradas pela administração ou pelo estado
- **WHEN** a tela de relatórios é carregada
- **THEN** a seleção de igreja é removida e nenhum relatório da igreja incompatível é exibido

#### Scenario: Igreja da sessão fica fora do filtro atual

- **GIVEN** não há igreja na consulta e a igreja guardada na sessão não pertence às opções filtradas
- **WHEN** o usuário acessa a tela de relatórios
- **THEN** nenhuma igreja fica selecionada e a tela orienta o usuário a escolher uma igreja compatível

#### Scenario: Igreja indisponível não vaza dados

- **GIVEN** a consulta informa uma igreja que não está entre as opções permitidas para os filtros atuais
- **WHEN** a requisição é processada
- **THEN** a listagem não chama o carregamento de relatórios para o identificador rejeitado

