# Proposta: Padronizar passagem de estados para as views de dependências

## Contexto e Motivação
Em várias áreas do sistema Check Planilha (Administrações, Usuários, Igrejas, Produtos, Relatórios, Auditoria, etc.), a lista de estados da federação brasileira (`config('brazil.states')`) é injetada nas views a partir dos controladores para permitir padronização e eventuais filtros ou renderizações consistentes. No caso do módulo de Dependências (`LegacyDepartmentController`), a ação `index` já passa a chave `states`, porém as ações de criação (`create`) e edição (`edit`) não passavam a variável `states`, quebrando o padrão adotado em outros módulos com formulários (como Usuários, Administrações e Igrejas).

## Escopo
- Disponibilizar a chave `states` (array associativo UF => Nome do estado vindo de `config('brazil.states', [])`) nas views `departments.create` e `departments.edit`.
- Atualizar e complementar os testes de feature de dependências garantindo que as views de `create` e `edit` recebam a variável `states`.
- Manter compatibilidade total e sem regressões na listagem, criação e edição de dependências.

## Rollback
Em caso de necessidade de reversão, basta retornar o método `create` e `edit` de `LegacyDepartmentController` para não passarem a chave `states` e reverter as asserções de teste correspondentes.
