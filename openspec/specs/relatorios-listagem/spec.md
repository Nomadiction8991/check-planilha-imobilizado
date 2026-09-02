# relatorios-listagem Specification

## Purpose
TBD - created by archiving change filtro-relatorios-busca-administracao. Update Purpose after archive.
## Requirements
### Requirement: Filtro por administração na seleção de relatórios
O sistema SHALL permitir filtrar a lista de congregações disponíveis na tela de relatórios por administração informada (`administracao_id`), além de fornecer as opções de administração para o formulário.

#### Scenario: Listagem de opções de administrações
- GIVEN que existem administrações cadastradas no banco
- WHEN o usuário acessa a tela de relatórios (`/reports`)
- THEN a view DEVE receber a lista de administrações ordenadas por descrição para permitir o filtro

#### Scenario: Filtragem dinâmica de administrações no select
- GIVEN a presença do select de administrações na tela de relatórios
- WHEN o usuário digita no campo de busca de administração
- THEN as opções do select de administração DEVEM ser filtradas instantaneamente sem recarregar a página

