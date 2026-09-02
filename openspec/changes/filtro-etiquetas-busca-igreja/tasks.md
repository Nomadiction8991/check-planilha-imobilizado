# Tasks: filtro-etiquetas-busca-igreja

- [x] 1.1 Adicionar campo de busca acessível associado ao select de igreja em `resources/views/labels/index.blade.php` (remover onchange automático)
- [x] 1.2 Implementar filtragem client-side (case-insensitive, placeholder preservado, estado sem resultados com mensagem e select desabilitado)
- [x] 1.3 Escrever teste de feature cobrindo presença do campo de busca em `GET /labels`
- [x] 1.4 Rodar `php -l` nos arquivos PHP alterados, `php artisan test` e `openspec validate filtro-etiquetas-busca-igreja --json`
