# Proposta: Filtro por Estado (UF) nas Administrações

## Contexto e Motivação
A tela de listagem de administrações (`/administrations`) atualmente permite filtrar apenas por busca textual genérica (`busca`: ID, descrição ou CNPJ). Com o crescimento da base de administrações em múltiplos estados brasileiros, é necessário filtrar diretamente por Unidade Federativa (UF), mantendo coerência com os demais cadastros e filtros do sistema.

## Objetivo
- Adicionar o parâmetro de filtro opcional `estado` (UF com 2 letras) no DTO `AdministrationFilters`.
- Atualizar o `LegacyAdministrationBrowserService` para aplicar o filtro `where('estado', $filters->state)` quando informado.
- Disponibilizar na interface de listagem (`administrations/index.blade.php`) um seletor de estado com a lista oficial de UFs de `config('brazil.states')`, preservando a seleção na paginação e no botão de limpar.
- Cobrir com testes de unidade e de integração/feature.
