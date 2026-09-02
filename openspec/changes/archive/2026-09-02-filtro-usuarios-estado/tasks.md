# Tarefas de Implementação

- [x] Criar testes unitários para `UserFiltersTest` cobrindo o campo de estado <!-- id: 1 -->
- [x] Atualizar o DTO `UserFilters` com o atributo `$state`, sanitização e `toQuery` <!-- id: 2 -->
- [x] Criar testes unitários em `LegacyUserBrowserServiceTest` testando paginação com filtro por `endereco_estado` <!-- id: 3 -->
- [x] Atualizar `LegacyUserBrowserService::paginate()` para filtrar por `endereco_estado` <!-- id: 4 -->
- [x] Atualizar `LegacyUserController::index()` para injetar `states` na view `users.index` <!-- id: 5 -->
- [x] Atualizar `resources/views/users/index.blade.php` com o campo seletor de estado (UF) <!-- id: 6 -->
- [x] Atualizar testes do controller e feature para verificar a passagem do filtro e renderização do seletor <!-- id: 7 -->
- [x] Validar a suíte de testes e o OpenSpec change <!-- id: 8 -->
