# Tasks: acesso-publico-busca-igreja

## 1. View pública com busca

- [x] 1.1 Adicionar campo de busca acessível em `resources/views/public-access/create.blade.php` vinculado a `select#comum_id`
- [x] 1.2 Implementar JS leve para filtrar `option`s por substring case-insensitive, tratar estado sem resultados com mensagem e desabilitar seletor quando necessário
- [x] 1.3 Validar visual e navegação por teclado/leitor de tela (label, aria-controls e região de status)

## 2. Testes

- [x] 2.1 Estender `tests/Feature/PublicAccessTest.php` para cobrir presença do campo de busca e filtragem de marcação
- [x] 2.2 Rodar `php -l` nos arquivos alterados e `php artisan test --filter=PublicAccess`

## 3. Validação e finalização

- [x] 3.1 Validar health local (`curl /login` e `curl /assinatura-publica`)
- [x] 3.2 Atualizar tasks com checkboxes e validar `openspec validate`
