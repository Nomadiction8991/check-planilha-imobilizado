# produtos-filtros-colapsaveis Specification

## Purpose

Tornar os filtros de produtos usáveis em telas pequenas, recolhendo o bloco de filtros no mobile sem remover o acesso às opções avançadas.

## ADDED Requirements

### Requirement: Filtros colapsáveis no mobile para listagem e verificação de produtos

O sistema SHALL exibir, nas telas `/products` (index) e `/products/verification` (verificação), o bloco de filtros (formulário + chips ativos) dentro de um contêiner colapsável cujo estado é controlado por um botão de alternância dedicado. No mobile (largura de viewport `≤860px`), o contêiner SHALL iniciar colapsado (filtros não visíveis) por padrão e SHALL expandir/recolher ao acionar o botão. No desktop (`≥861px`), o contêiner SHALL permanecer sempre expandido (filtros visíveis) e o botão de alternância SHALL estar oculto.

#### Scenario: Mobile — filtros colapsados por padrão

- WHEN o usuário visita `/products` ou `/products/verification` em viewport ≤860px sem interação prévia
- THEN o contêiner de filtros está colapsado (não visível) e o botão “Filtros” está visível com `aria-expanded="false"`

#### Scenario: Mobile — expandir filtros

- WHEN o usuário aciona o botão de alternância de filtros no mobile
- THEN o contêiner de filtros torna-se visível e o botão passa a `aria-expanded="true"`

#### Scenario: Mobile — recolher filtros

- WHEN o contêiner está expandido no mobile e o usuário aciona novamente o botão
- THEN o contêiner volta ao estado colapsado e `aria-expanded` retorna a `false`

#### Scenario: Desktop — filtros sempre visíveis e botão oculto

- WHEN o usuário visita as mesmas telas em viewport ≥861px
- THEN o contêiner de filtros está visível independentemente do estado mobile e o botão de alternância não é exibido

#### Scenario: Botão indica filtros ativos

- WHEN há um ou mais critérios de filtro ativos (ex.: `administracao_id`, `comum_id`, `estado`, `busca`, `status`)
- THEN o rótulo do botão inclui a contagem de filtros ativos (ex.: “Filtros · 2 ativos”); quando nenhum filtro está ativo, o rótulo é apenas “Filtros”

#### Scenario: Filtragem e paginação preservadas com container colapsável

- WHEN o usuário aplica filtros e navega por paginação com o contêiner colapsado ou expandido
- THEN os parâmetros de filtro continuam sendo enviados e preservados normalmente, sem depender do estado visual do contêiner

#### Scenario: Compatibilidade com chips e controles existentes

- WHEN o contêiner está expandido
- THEN todos os controles existentes (busca de administração/igreja, UF, busca geral, dependência, tipo, status, chips com remoção seletiva, botões Filtrar/Limpar) permanecem funcionais e acessíveis dentro do contêiner
