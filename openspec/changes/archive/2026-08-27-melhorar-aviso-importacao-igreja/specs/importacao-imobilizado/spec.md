## MODIFIED Requirements

### Requirement: Aviso de escopo de importação na tela inicial
A tela de importação SHALL exibir um aviso proeminente em vermelho alertando que a importação processa a **igreja inteira** (todas as dependências), não apenas um setor ou dependência selecionada.

#### Scenario: Usuário acessa a tela de importação
- **WHEN** o usuário abre a página de importação de planilhas
- **THEN** o sistema exibe um banner vermelho com o texto "A importação processa a **igreja inteira** (todas as dependências), não apenas um setor"
- **AND** o banner usa cor de fundo vermelha e borda esquerda vermelha para indicar atenção
- **AND** o aviso de performance ("Prefira planilha filtrada por igreja") permanece como nota secundária

### Requirement: Aviso de escopo de importação na prévia
A prévia da importação SHALL exibir o mesmo aviso de escopo antes da tabela de igrejas detectadas, reforçando que cada igreja selecionada será importada com todas as suas dependências.

#### Scenario: Usuário visualiza a prévia com igrejas detectadas
- **WHEN** o usuário abre a prévia de uma importação com igrejas detectadas
- **THEN** o sistema exibe um banner vermelho acima da seção "Igrejas detectadas" com o texto "A importação processa a **igreja inteira** — ao confirmar, todos os setores da(s) igreja(s) selecionada(s) serão importados"
- **AND** o banner usa estilo visual consistente com o da tela inicial (vermelho, borda esquerda)
- **AND** o aviso aparece antes que o usuário possa selecionar ações por igreja/dependência