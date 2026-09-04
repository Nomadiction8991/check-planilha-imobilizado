## ADDED Requirements

### Requirement: Submissão automática de filtros de relatórios reinicia a paginação
A atualização automática de filtros da tela de relatórios (`GET /reports`) SHALL limpar e desabilitar os parâmetros de paginação (`page` e `pagina`) antes de submeter o formulário automaticamente por mudança de administração, estado ou igreja, de modo que o resultado volte à primeira página com os novos critérios.

#### Scenario: Alterar filtro em página avançada
- GIVEN o usuário está com paginação em página 3 (quando aplicável)
- WHEN altera um filtro que dispara submissão automática (select de administração, estado ou igreja)
- THEN o GET enviado não contém parâmetro de página e o resultado exibe a primeira página correspondente aos novos filtros
