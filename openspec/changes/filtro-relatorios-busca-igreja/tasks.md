# Tasks: Busca filtrável de igreja na lista de relatórios

- [x] Adicionar campo de busca digitável em `resources/views/reports/index.blade.php` associado ao select de igreja (`comum_id`), com input `type="search"`, `aria-controls`, e elemento `role="status"` para mensagem sem resultados
- [x] Adicionar JS inline que filtra em tempo real as opções do select por correspondência case-insensitive, oculta/desabilita não correspondentes, desabilita o select e exibe mensagem quando não há match, e restaura tudo ao limpar a busca
- [x] Adicionar teste de feature em `tests/Feature/LegacyReportPagesTest.php` cobrindo: presença do campo de busca, select com `data-*` correto, e elemento de status acessível na tela `GET /reports`
- [x] Rodar `php -l` nos arquivos PHP alterados, `php artisan test` e `openspec validate --change filtro-relatorios-busca-igreja --json`
- [x] Verificar saúde local `curl 200` em `/reports` e `/login`
