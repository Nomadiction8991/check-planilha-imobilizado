## 1. Implementação

- [x] 1.1 Atualizar autosubmit de `resources/views/products/index.blade.php` para limpar e desabilitar `page`/`pagina` antes da submissão automática
- [x] 1.2 Atualizar autosubmit de `resources/views/products/verification.blade.php` com o mesmo comportamento
- [x] 1.3 Validar `openspec validate --change produtos-autosubmit-reinicia-paginacao --strict` e `php -l` nos arquivos alterados

## 2. Verificação

- [x] 2.1 Rodar `php artisan test` e sondar `curl http://127.0.0.1:8084/products` e `/products/verification` retornando 200
