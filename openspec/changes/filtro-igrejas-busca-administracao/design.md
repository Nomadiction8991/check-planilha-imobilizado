# Design: Filtro de administração na listagem de igrejas

## Decisões Técnicas

1. **DTO `ChurchFilters`**:
   - Incluir propriedade `public ?int $administrationId` nullable.
   - Extrair `administracao_id` no `fromRequest()`.
   - Adicionar `administracao_id` no array retornado por `toQuery()`.

2. **Serviço `LegacyChurchBrowserService`**:
   - Adicionar cláusula `when($filters->administrationId !== null, fn($q) => $q->where('administracao_id', $filters->administrationId))` na consulta do `paginate()`.

3. **Controller `LegacyChurchController`**:
   - Enviar a lista de administrações (`$this->churchs->administrationOptions()`) para a view `churches/index`.

4. **Interface Blade (`churches/index.blade.php`)**:
   - Adicionar campo de busca textual e select de administração com atributo `data-churches-admin-search` e script vanilla de filtragem em tempo real, idêntico ao padrão maduro de `users/index.blade.php`.
