# Design Técnico: Filtro por Estado na Listagem de Igrejas

## Abordagem
- **DTO (`App\DTO\ChurchFilters`)**: Adicionar a propriedade `public ?string $state` no construtor, sanitizar no método estático `fromRequest` usando `mb_strtoupper(mb_substr(trim($raw), 0, 2))` e propagar em `toQuery()` para paginação limpa.
- **Service (`App\Services\LegacyChurchBrowserService`)**: Inserir condicional `when($filters->state !== null && $filters->state !== '', fn($q) => $q->where('estado', $filters->state))` na query de paginação.
- **Controller (`App\Http\Controllers\LegacyChurchController`)**: Passar `'states' => (array) config('brazil.states', [])` para a view `churches.index`.
- **View (`resources/views/churches/index.blade.php`)**: Adicionar o `<select name="estado">` no formulário de filtros, com opção "Todos os estados" e seleção automática com `@selected($filters->state === $stateCode)`.
- **Testes**:
  - `ChurchFiltersTest`: testar leitura de estado e sanitização.
  - `LegacyChurchBrowserServiceTest`: testar filtro por estado isolado e combinado.
  - `LegacyChurchControllerTest`: testar renderização do select e passagem do filtro ao serviço.
