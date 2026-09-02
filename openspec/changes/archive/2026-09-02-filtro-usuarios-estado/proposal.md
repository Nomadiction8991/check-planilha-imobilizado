# Proposta de Mudança: Filtro por Estado (UF) na Listagem de Usuários

## Por quê
Na gestão de cadastros do sistema, os módulos de Administrações, Igrejas e Dependências já receberam suporte ao filtro por Unidade Federativa (UF). O cadastro de usuários possui o campo `endereco_estado` no modelo de dados `Usuario`, mas a tela de listagem de usuários (`users.index`) permitia filtrar apenas por administração, busca textual e status. Adicionar o filtro por UF ao DTO `UserFilters`, ao serviço `LegacyUserBrowserService` e à interface de consulta padroniza a experiência do usuário e agiliza a localização de operadores por estado.

## O que
- Adicionar a propriedade opcional `public ?string $state` em `App\DTO\UserFilters`, com sanitização para 2 caracteres maiúsculos em `fromRequest()` e inclusão em `toQuery()`.
- Atualizar a cláusula de busca em `LegacyUserBrowserService::paginate()` para filtrar por `endereco_estado` quando o filtro for fornecido.
- Injetar a lista de estados (`config('brazil.states')`) na view `users.index` através do `LegacyUserController::index()`.
- Adicionar o campo seletor de estado (UF) no formulário de filtros da view `resources/views/users/index.blade.php`.
- Criar e atualizar testes unitários e de integração (TDD) para cobrir o novo filtro em `UserFiltersTest`, `LegacyUserBrowserServiceTest` e `LegacyUserControllerTest`.
