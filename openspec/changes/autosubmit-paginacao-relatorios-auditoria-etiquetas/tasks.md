## 1. Implementação
- [x] 1.1 Atualizar `resources/views/reports/index.blade.php` para limpar e desabilitar `page`/`pagina` antes da submissão automática
- [x] 1.2 Atualizar `resources/views/audits/index.blade.php` para limpar e desabilitar `page`/`pagina` antes da submissão automática (inclui busca com debounce e mudança de selects/datas)
- [x] 1.3 Atualizar `resources/views/labels/index.blade.php` substituindo `history.replaceState` por limpeza/desativação de campos `page`/`pagina` do formulário
- [x] 1.4 Validar `openspec validate --change autosubmit-paginacao-relatorios-auditoria-etiquetas --strict` e `php -l` nos arquivos alterados

## 2. Verificação
- [x] 2.1 Estender testes de feature existentes (ou adicionar asserts) para garantir que o script de autosubmit de relatórios, auditoria e etiquetas contém `resetPage` e a lógica de desabilitar `page`/`pagina`
- [x] 2.2 Rodar `php artisan test` e sondar `curl http://127.0.0.1:8084/reports`, `/audits`, `/labels` retornando 200
