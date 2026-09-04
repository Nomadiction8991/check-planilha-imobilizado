## ADDED Requirements

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
