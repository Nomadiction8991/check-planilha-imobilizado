# Design Técnico: Barra de Ações Rápidas e Atalhos

## Componentes

### 1. `resources/views/partials/quick-actions.blade.php`
- Componente Blade contendo a barra de ações rápidas, modal acessível e script nativo de navegação.
- Suporte a tema claro e escuro herdando as variáveis CSS do sistema.
- Totalmente desacoplado de bibliotecas pesadas de terceiros (JavaScript nativo puro).

### 2. Acessibilidade
- Papéis ARIA (`role="toolbar"`, `role="dialog"`).
- Isolamento para não interceptar digitação em campos `input`, `textarea` e `select`.
