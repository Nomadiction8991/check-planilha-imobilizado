## ADDED Requirements

### Requirement: Submissão automática de filtros reinicia a paginação
A atualização automática de filtros nas telas de produtos (`/products` e `/products/verification`) SHALL limpar e desabilitar os parâmetros de paginação (`page` e `pagina`) antes de submeter o formulário, de modo que o resultado volte à primeira página com os novos critérios.

#### Scenario: Alterar filtro na página 3
- GIVEN o usuário está na página 3 com paginação ativa
- WHEN altera um filtro que dispara submissão automática (select ou busca)
- THEN o GET enviado não contém parâmetro de página e o resultado exibe a primeira página

#### Scenario: Remover filtro via autosubmit
- WHEN a submissão automática ocorre após remoção de um critério que reduz o total de páginas
- THEN o parâmetro de página não é enviado e a navegação não tenta renderizar página inexistente
