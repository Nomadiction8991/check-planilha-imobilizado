## 1. Partial de chips

- [x] 1.1 Criar `resources/views/products/partials/active-filter-chips.blade.php` que liste chips apenas para filtros ativos, resolvendo rótulos via coleções recebidas e `config('brazil.states')`; cada chip com link de remoção que preserva demais filtros e botão com `aria-label`; container com `aria-live="polite"` e estilo capsular responsivo.
- [x] 1.2 Cobrir helpers de query (construção de URL sem o parâmetro removido) dentro da partial, com fallback de rótulo quando lookup falhar.

## 2. Integração nas telas

- [x] 2.1 Incluir a partial em `resources/views/products/index.blade.php` logo abaixo do bloco `.filters`.
- [x] 2.2 Incluir a partial em `resources/views/products/verification.blade.php` no mesmo ponto.

## 3. Validação

- [x] 3.1 Rodar `php -l` nos arquivos PHP/Blade alterados.
- [x] 3.2 Validar OpenSpec: `openspec validate --change chips-filtros-ativos-produtos`.
- [x] 3.3 Teste manual (curl 200 em `/products` e `/products/verification` com/sem filtros) e suíte relevante (`php artisan test --filter=LegacyProduct`).
