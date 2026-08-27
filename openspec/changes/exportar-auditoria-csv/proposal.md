## Why

A tela de auditoria permite consultar quem fez o quê no sistema, mas só dentro da interface: sem exportação, a conferência externa (planilha de controle da imobiliária, arquivamento, análise em ferramentas de dados) exige copiar linha por linha. Os relatórios do sistema já seguem um padrão consolidado de exportação CSV com BOM e separador ponto e vírgula; a auditoria precisa do mesmo recurso.

## What Changes

- Novo método na camada de serviço de auditoria que gera o conteúdo CSV de TODOS os eventos que casam com os filtros atuais (busca, módulo, período), respeitando o escopo do usuário — não apenas a página atual.
- Nova rota GET na tela de auditoria que devolve esse arquivo como download.
- Botão "Exportar CSV" na tela de auditoria, ao lado dos filtros, que preserva os filtros atuais na URL.
- Mensagem amigável quando não há eventos para exportar.

## Capabilities

### New Capabilities

### Modified Capabilities
- `auditoria`: exportação CSV dos eventos auditados respeitando filtros e escopo.

## Impact

Contrato e serviço de auditoria (novo método de exportação), controller de auditoria (nova ação), rotas (nova rota GET protegida pela permissão de ver auditoria) e a view da tela de auditoria (botão de exportar). Testes unitários do serviço e testes de feature do controller cobrem o comportamento. Nenhuma mudança de schema nem de dependências.
