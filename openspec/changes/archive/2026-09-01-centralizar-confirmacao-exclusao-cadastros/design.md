# Design: Confirmação Declarativa em Exclusão de Cadastros

## Contexto
O layout base `resources/views/layouts/migration.blade.php` possui um event listener global no evento `submit` que lê `form.dataset.confirm`:

```javascript
document.addEventListener('submit', (event) => {
    const form = event.target;
    const message = form instanceof HTMLFormElement ? form.dataset.confirm : '';

    if (message && !window.confirm(message)) {
        event.preventDefault();
    }
});
```

As views de Administrações, Dependências e Tipos de Bem ainda usavam `onclick="return confirm('...')"` no botão de submissão do formulário.

## Mudanças Arquiteturais
1. Substituir `onclick="return confirm('...')" ` nos botões por `data-confirm="..."` na tag `<form>` nas 3 views.
2. Garantir consistência nas mensagens:
   - Administrações: `data-confirm="Excluir esta administração?"`
   - Dependências: `data-confirm="Excluir esta dependência?"`
   - Tipos de Bem: `data-confirm="Excluir este tipo de bem?"`
3. Adicionar asserções nos testes automatizados para verificar que as views contêm `data-confirm` e não contêm `onclick="return confirm"`.
