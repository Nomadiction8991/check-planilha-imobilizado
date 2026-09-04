## ADDED Requirements

### Requirement: Filtros colapsáveis no mobile para etiquetas

O sistema SHALL exibir, na tela `/labels`, o bloco de filtros dentro de um contêiner colapsável controlado por botão de alternância dedicado com o mesmo comportamento de produtos e relatórios. No mobile (`≤860px`) o contêiner inicia colapsado e no desktop (`≥861px`) sempre expandido, com o botão oculto no desktop. O rótulo SHALL refletir filtros ativos de etiquetas (`administracao_id`, `estado`, `comum_id`, `dependencia`).

#### Scenario: Mobile — filtros de etiquetas iniciam colapsados

- WHEN o usuário visita `/labels` em viewport ≤860px
- THEN o contêiner de filtros está colapsado e o botão exibe `aria-expanded="false"`

#### Scenario: Desktop — filtros visíveis e botão oculto

- WHEN o usuário visita `/labels` em viewport ≥861px
- THEN o contêiner está visível e o botão não é exibido

#### Scenario: Contagem de ativos no botão de etiquetas

- WHEN há filtros ativos em `/labels`
- THEN o botão indica "Filtros · N ativos"; sem filtros, apenas "Filtros"

#### Scenario: Controles permanecem funcionais quando expandido

- WHEN o contêiner de `/labels` está expandido
- THEN os controles de filtro permanecem funcionais
