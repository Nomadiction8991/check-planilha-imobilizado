# Tasks: filtro-produtos-busca-igreja

## 1. Views de produtos com busca

- [x] 1.1 Adicionar campo de busca acessível associado ao select de igreja em `resources/views/products/index.blade.php`
- [x] 1.2 Adicionar o mesmo campo/busca em `resources/views/products/verification.blade.php`
- [x] 1.3 Implementar JS leve por view para filtrar options por substring case-insensitive, tratar estado sem resultados e desabilitar seletor

## 2. Testes

- [x] 2.1 Estender testes de feature para cobrir presença do campo de busca e atributos de acessibilidade nas duas telas
- [x] 2.2 Rodar `php -l` nos arquivos alterados e `php artisan test --filter=LegacyProduct`

## 3. Validação e finalização

- [x] 3.1 Validar health local (`curl /login` e `curl /products` quando autenticado ou 302 quando não)
- [x] 3.2 Validar `openspec validate --specs` / `--changes` e atualizar checkboxes
