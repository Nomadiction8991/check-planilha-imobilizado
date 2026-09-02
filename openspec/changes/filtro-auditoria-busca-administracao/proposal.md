# Proposta: Filtro por Administração e Busca Rápida na Tela de Auditoria

## Intenção
Permitir que administradores e usuários autorizados filtrem os registros de auditoria por administração específica através de uma caixa de busca rápida com preenchimento/filtragem assistida de opções, alinhando a experiência da tela de auditoria ao padrão de usabilidade e acessibilidade já implementado nos demais cadastros e consultas do sistema (produtos, relatórios, departamentos, igrejas, usuários e etiquetas).

## Escopo
- Adicionar parâmetro de filtro por administração (`administracao_id`) no serviço de auditoria (`LegacyAuditTrailServiceInterface` / `LegacyAuditTrailService`) para paginação e exportação CSV.
- Atualizar o controller de auditoria (`LegacyAuditController`) para receber, validar e repassar o filtro de administração, injetando a lista de administrações disponíveis e o valor selecionado na view.
- Atualizar a view de auditoria (`resources/views/audits/index.blade.php`) com campo de busca de administração (`data-audits-admin-search`), select associado (`data-audits-admin-select`), mensagem de feedback acessível (`data-audits-admin-status`) e script inline de filtragem dinâmica sem quebrar em telas menores.
- Garantir que a exportação CSV preserve o filtro de administração selecionado.
- Adicionar testes de unidade e feature cobrindo o novo filtro, preservação em requisições e coerência com escopo de permissão.
