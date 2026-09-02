# Proposta de Padronização: Passagem de Estados nas Views de Importação de Planilhas

## Por que
Seguindo as padronizações recentes nos controllers de Administrações, Auditoria, Dependências e Tipos de Bens, o controller `SpreadsheetImportController` deve injetar explicitamente o array de estados brasileiros (`states`) obtido de `config('brazil.states', [])` para a view `spreadsheets.import`. Isso assegura uniformidade arquitetural no ecossistema e evita dependência de fallbacks globais.

## O que
- Adicionar `'states' => (array) config('brazil.states', [])` no método `create()` de `SpreadsheetImportController`.
- Testar e validar que a view recebe a variável `states` adequadamente.
