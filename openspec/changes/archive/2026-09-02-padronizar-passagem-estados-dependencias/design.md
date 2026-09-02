# Design Técnico: Padronização de Estados nas Views de Dependências

## Abordagem Técnica
1. No método `create()` de `LegacyDepartmentController`:
   - Adicionar `'states' => (array) config('brazil.states', [])` ao array de dados retornado para `view('departments.create', [...])`.
2. No método `edit()` de `LegacyDepartmentController`:
   - Adicionar `'states' => (array) config('brazil.states', [])` ao array de dados retornado para `view('departments.edit', [...])`.
3. Nos testes de feature (`LegacyDepartmentControllerTest`):
   - Adicionar asserções nos testes `testCreatePageRendersForm` e `testEditPageRendersForm` (ou testes dedicados) validando a presença e formato da variável `states` na view.
