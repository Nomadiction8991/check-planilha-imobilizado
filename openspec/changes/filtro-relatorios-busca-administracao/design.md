# Technical Design: Filtro por Administração na Listagem de Relatórios

## Architecture & Implementation Details

1. **Contrato e Serviço:**
   - Adicionar `public function administrationOptions(): Collection;` em `App\Contracts\LegacyReportServiceInterface`.
   - Adicionar `public function churchOptions(?int $administrationId = null): Collection;` ou manter retrocompatível `churchOptions(?int $administrationId = null): Collection;` em `LegacyReportServiceInterface` e `LegacyReportService`.
   - Implementar `administrationOptions(): Collection` em `LegacyReportService` consultando `Administracao::query()->orderBy('descricao')->get(['id', 'descricao'])`.
   - Implementar filtro opcional por `administracao_id` em `churchOptions(?int $administrationId = null)` quando o id for informado.

2. **Controller:**
   - Em `LegacyReportController@index`:
     - Obter `$administrationId = $request->integer('administracao_id') ?: null;`
     - Passar `administrations` e `selectedAdministrationId` para a view `reports.index`.
     - Passar `churches` filtrado ou completo conforme `$this->reports->churchOptions($administrationId)`.

3. **View `resources/views/reports/index.blade.php`:**
   - Inserir campo `Buscar administração` com `data-reports-admin-search` e o `<select name="administracao_id" data-reports-admin-select>`.
   - Script embutido com debounce e filtro em tempo real sobre o select de administrações com mensagem de status acessível `data-reports-admin-status`.

4. **Testes:**
   - Unit: `LegacyReportServiceTest` cobrindo `administrationOptions` e `churchOptions` com/sem filtro.
   - Feature: `LegacyReportPagesTest` cobrindo renderização da busca/select de administração e submissão com `administracao_id`.
