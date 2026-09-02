# Proposta: Filtro por Estado na Listagem de Dependências

## Motivação
Na listagem de dependências (`departments.index`), os usuários já podem filtrar por administração, igreja e termo de busca (descrição). No entanto, para redes com centenas de dependências espalhadas por diferentes estados, localizar dependências vinculadas a igrejas de uma UF específica exige saber a administração ou o código exato da igreja. Adicionar um filtro por estado (UF da igreja vinculada) alinha a tela de dependências ao padrão já existente nas telas de igrejas e administrações.

## Escopo
- Adicionar campo opcional `estado` (UF) em `DepartmentFilters` (com sanitização em maiúsculas e 2 caracteres).
- Atualizar `LegacyDepartmentBrowserService` para filtrar por estado da igreja (`comum.estado`) quando o filtro for informado.
- Atualizar a view `departments/index.blade.php` com o select de estados (UF) a partir do config `brazil.states` fornecido pelo controller.
- Atualizar `LegacyDepartmentController::index` para passar a lista de estados para a view.
- Adicionar testes de unidade e testes de integração cobrindo o filtro por estado em dependências.
