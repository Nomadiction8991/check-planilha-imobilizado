# Proposta de Restrição de Dependências ao Escopo Administrativo

## Contexto e Motivação
Em alinhamento com a arquitetura multi-tenant por escopo administrativo do Check Planilha (já presente na navegação de igrejas, produtos, importação e relatórios), a consulta e opções de dependências (`LegacyDepartmentBrowserService`) devem respeitar rigorosamente o escopo do usuário não-administrador. Usuários restritos a administrações específicas só devem visualizar e ter acesso a dependências vinculadas às igrejas sob sua administração permitida.

## Escopo da Mudança
- Modificar `LegacyDepartmentBrowserService` para filtrar por `currentAdministrationScopeIds()` nas listagens (`paginate`), opções de igrejas (`churchOptions`), opções de administrações (`administrationOptions`) e contagem total (`countAll`).
- Manter acesso irrestrito para usuários administradores (`is_admin = true`) ou sessões globais sem escopo definido.
- Implementar testes unitários e de integração validando o comportamento com e sem escopo restrito.
