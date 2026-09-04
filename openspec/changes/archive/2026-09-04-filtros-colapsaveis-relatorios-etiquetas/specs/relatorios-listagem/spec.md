## ADDED Requirements

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
