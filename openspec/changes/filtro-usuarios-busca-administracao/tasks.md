## 1. View

- [ ] 1.1 Adicionar campo de busca digitável em `resources/views/users/index.blade.php` associado ao select de administração (`administracao_id`), com input `type="search"`, `aria-controls`, e elemento `role="status"` para mensagem sem resultados
- [ ] 1.2 Adicionar script inline leve para filtrar opções do select por correspondência case-insensitive, ocultando/desabilitando não correspondentes, tratando placeholder "Todas" e estado sem resultados (mensagem + select desabilitado)

## 2. Validação

- [ ] 2.1 Validar com `openspec validate --change filtro-usuarios-busca-administracao` e garantir suíte verde (`php artisan test` em sqlite :memory:)
- [ ] 2.2 Confirmar saúde da app após alteração (`curl http://127.0.0.1:8084/login` 200 e /users com sessão válida renderiza sem erro)
