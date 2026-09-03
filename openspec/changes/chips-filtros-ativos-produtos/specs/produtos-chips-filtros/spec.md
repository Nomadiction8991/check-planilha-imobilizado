# produtos-chips-filtros Specification

## Purpose

Resume visualmente os filtros ativos nas telas de produtos e permitir remoção seletiva por chip, melhorando orientação e eficiência em filtros combinados.

## ADDED Requirements

### Requirement: Chips de filtros ativos em listagem e verificação de produtos

O sistema SHALL exibir, nas telas `/products` (index) e `/products/verification` (verificação), uma barra de chips imediatamente abaixo do bloco de filtros existentes que sintetize apenas os filtros ativos no momento. Cada chip SHALL corresponder a um único critério ativo entre: administração (`administracao_id`), igreja (`comum_id`), estado/UF (`estado`), dependência (`dependencia_id`), tipo de bem (`tipo_bem_id`), status (`status`) e busca geral (`busca`/`nome`/`codigo` normalizada para `busca`). O rótulo de cada chip SHALL apresentar o nome do filtro e o valor legível correspondente obtido dos dados já disponíveis em tela (por exemplo, descrição da administração/igreja/dependência/tipo, label do status, UF e texto da busca). Chips SHALL NOT serem exibidos quando nenhum filtro estiver ativo. Opcionalmente, um chip adicional para o flag `somente_novos` MAY ser exibido quando ativo.

#### Scenario: Nenhum filtro ativo — barra oculta

- WHEN o usuário visita `/products` ou `/products/verification` sem nenhum parâmetro de filtro ativo
- THEN a barra de chips não é exibida

#### Scenario: Um filtro ativo — um chip visível

- WHEN o usuário aplica apenas `estado=SP`
- THEN exatamente um chip é exibido com rótulo correspondente ao estado (ex.: "Estado: SP — São Paulo")

#### Scenario: Múltiplos filtros ativos — múltiplos chips

- WHEN os filtros incluem `administracao_id`, `comum_id`, `busca` e `status`
- THEN a barra mostra um chip por critério ativo, cada um com rótulo distinto

#### Scenario: Remoção seletiva por chip

- WHEN o usuário clica no botão de remoção (×) de um chip específico
- THEN a página recarrega preservando todos os demais filtros ativos e removendo apenas o critério do chip clicado

#### Scenario: Remoção do último filtro

- WHEN o único filtro ativo é removido via chip
- THEN a listagem passa a exibir todos os registros (sem filtros) e a barra de chips deixa de ser exibida

#### Scenario: Chips acessíveis

- WHEN a barra de chips é exibida
- THEN a região possui `aria-live="polite"` e cada botão de remoção possui `aria-label` que descreve o filtro removido (ex.: "Remover filtro de igreja")

#### Scenario: Compatibilidade com filtros existentes

- WHEN chips estão visíveis
- THEN os controles de filtro, o botão "Filtrar" e o botão "Limpar" existentes permanecem funcionais; paginação e links de ordenação continuam preservando os filtros ativos conforme comportamento atual
