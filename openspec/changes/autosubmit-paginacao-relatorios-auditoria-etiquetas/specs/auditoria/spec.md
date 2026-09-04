## ADDED Requirements

### Requirement: Submissão automática de filtros de auditoria reinicia a paginação
A atualização automática de filtros da tela de auditoria (`GET /audits`) SHALL limpar e desabilitar os parâmetros de paginação (`page` e `pagina`) antes de submeter o formulário automaticamente por mudança de administração, módulo, datas ou busca geral, de modo que o resultado volte à primeira página com os novos critérios.

#### Scenario: Alterar filtro em página avançada
- GIVEN o usuário está em uma página posterior da auditoria
- WHEN altera um filtro que dispara submissão automática (select, data ou busca com debounce)
- THEN o GET enviado não contém parâmetro de página e o resultado exibe a primeira página
