## ADDED Requirements

### Requirement: Submissão automática de filtros de etiquetas reinicia a paginação
A atualização automática de filtros da tela de etiquetas (`GET /labels`) SHALL limpar e desabilitar os parâmetros de paginação (`page` e `pagina`) antes de submeter o formulário automaticamente por mudança de administração, estado, igreja ou dependência. O reset SHALL ocorrer desabilitando campos do formulário (não via `history.replaceState`).

#### Scenario: Alterar dependência em página avançada
- GIVEN o usuário está em uma página posterior de etiquetas
- WHEN altera um filtro que dispara submissão automática
- THEN o GET enviado não contém parâmetro de página e o resultado exibe a primeira página

#### Scenario: Reset não usa history.replaceState
- WHEN a submissão automática é disparada
- THEN a paginação é reiniciada limpando e desabilitando campos `page`/`pagina` do formulário, sem manipular `window.history`
