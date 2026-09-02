# Proposta de Mudança: Filtro por Estado na Listagem de Igrejas

## Por que mudar?
Atualmente, a tela de listagem de igrejas (`/churches`) permite filtrar apenas por administração e por termo de busca (código/descrição). Em bases com muitas congregações espalhadas pelo Brasil, os usuários precisam filtrar rapidamente as igrejas pertencentes a um determinado Estado (UF) sem depender exclusivamente da seleção de administração ou de busca textual.

## O que será feito?
1. Estender o DTO `ChurchFilters` para receber e sanitizar o parâmetro opcional `estado` (código UF com 2 caracteres em maiúsculas).
2. Atualizar o serviço `LegacyChurchBrowserService` para incluir a cláusula `where('estado', $filters->state)` na paginação quando o filtro for informado.
3. Atualizar o `LegacyChurchController` para injetar a lista de estados disponíveis na view de listagem.
4. Adicionar o campo select de Estado (UF) na barra de filtros de `resources/views/churches/index.blade.php`.
5. Cobrir a nova funcionalidade com testes automatizados unitários e de feature.
