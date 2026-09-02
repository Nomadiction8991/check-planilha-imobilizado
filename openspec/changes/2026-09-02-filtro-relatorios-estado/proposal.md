# Proposta: Filtro por Estado na Seleção de Relatórios

## Motivação
Na tela de listagem de relatórios (`/reports`), usuários frequentemente precisam filtrar as opções de igreja pelo estado (UF) em que estão localizadas, especialmente quando operam em administrações que cobrem múltiplos estados ou quando desejam reduzir o volume de opções no seletor de igrejas.

## Escopo
- Adicionar parâmetro e filtro por estado (`estado` / UF) na listagem de relatórios.
- Atualizar `LegacyReportServiceInterface` e `LegacyReportService::churchOptions(?int $administrationId = null, ?string $state = null)` para permitir filtrar as igrejas por estado (`estado` na tabela `comuns`).
- Injetar lista de estados do Brasil (`config('brazil.states')`) e estado selecionado na view `reports.index`.
- Incluir o campo select de Estado (UF) nos filtros de `reports/index.blade.php`.
