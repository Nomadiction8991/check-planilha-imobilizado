## ADDED Requirements

### Requirement: Filtro de Estado nas Etiquetas
The system MUST permit filtering churches by Brazilian State (UF) in the labels copying screen.

#### Scenario: Filtrar congregações por estado na tela de etiquetas
- GIVEN um usuário autenticado acessando a tela de cópia de etiquetas
- WHEN o usuário seleciona um estado no campo Estado (UF)
- THEN apenas as igrejas pertencentes àquele estado (e administração se informada) são exibidas no seletor de congregações e a lista de estados é renderizada a partir da configuração padrão.
