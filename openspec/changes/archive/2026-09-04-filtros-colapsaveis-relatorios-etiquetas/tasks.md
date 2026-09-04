## 1. Especificação e estrutura

- [x] 1.1 Validar deltas OpenSpec das duas capabilities e mapear contagem de ativos por tela (relatórios: administracao_id/estado/comum_id; etiquetas: + dependencia).
- [x] 1.2 Definir marcadores reutilizáveis para botão/painel sem quebrar padrões existentes de produtos.

## 2. Implementação

- [x] 2.1 Em `resources/views/reports/index.blade.php`, adicionar botão `data-product-filters-toggle`/`data-reports-filters-toggle` e envolver `form` em painel `data-product-filters-panel` com `data-active-count` calculado no Blade.
- [x] 2.2 Em `resources/views/labels/index.blade.php`, idem para etiquetas (incluir dependencia na contagem).
- [x] 2.3 Em `resources/views/layouts/migration.blade.php`, reutilizar CSS/JS de toggle e painel já generalizados para os novos seletores, mantendo compatibilidade desktop/mobile.
- [x] 2.4 Rodar `php -l` nos arquivos alterados.

## 3. Testes e entrega

- [x] 3.1 Adicionar testes de view em `LegacyReportPagesTest` e `LegacyProductUtilityCompatibilityTest` (ou dédié) verificando presença de toggle/painel, `aria-expanded` e contagem.
- [x] 3.2 Executar suíte PHPUnit relevante (reports, labels) e validar saúde local/start (`curl /login` 200).
- [x] 3.3 Validar o change com OpenSpec; commit, push na main e confirmar deploy verde após a entrega.
