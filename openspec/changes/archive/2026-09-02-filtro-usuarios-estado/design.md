# Design Técnico: Filtro por Estado na Listagem de Usuários

## Abordagem
1. **DTO `UserFilters`**:
   - Adicionar `public ?string $state` no construtor.
   - Tratar e sanitizar `estado` em `fromRequest()`: converter para string, trim, upper case, pegar 2 primeiros caracteres.
   - Em `toQuery()`, adicionar `estado` se `$state !== null && $state !== ''`.

2. **Serviço `LegacyUserBrowserService`**:
   - Em `paginate()`, adicionar condição `->when($filters->state !== null && $filters->state !== '', fn ($query) => $query->where('endereco_estado', $filters->state))`.

3. **Controller `LegacyUserController`**:
   - No método `index()`, passar `'states' => (array) config('brazil.states', [])` para a view `users.index`.

4. **View `resources/views/users/index.blade.php`**:
   - Adicionar o bloco `<label class="filters-principal">` com `<select name="estado" id="users-estado-select">` e opções de estados antes do campo de busca textual.

5. **Testes**:
   - Unitário: `tests/Unit/DTO/UserFiltersTest.php` cobrindo sanitização e serialização de query.
   - Unitário: `tests/Unit/Services/LegacyUserBrowserServiceTest.php` cobrindo a query com filtro de estado.
   - Unitário/Feature de Controller: `tests/Unit/Controllers/LegacyUserControllerTest.php` e `tests/Feature/LegacyUserManagementTest.php` cobrindo o envio do filtro e renderização do seletor.
