# Proposta: Filtro e busca de administração na listagem e cópia de etiquetas

## Why
A tela de geração e cópia de etiquetas (`/labels`) possui seleção de igreja/congregação, mas não permitia filtrar congregações por administração específica, diferentemente das outras telas do sistema (produtos, relatórios, importação, congregações, dependências, etc.). Para usuários com permissão geral ou em ambientes multirregionais com dezenas de igrejas, selecionar a igreja correta sem filtro prévio de administração tornava a navegação demorada.

## What Changes
- Disponibilizar opções de administrações na view de etiquetas (`LegacyRouteCompatibilityController::labels`).
- Permitir passar o parâmetro `administracao_id` via request para filtrar as opções de igrejas disponíveis quando especificado.
- Incluir campo de busca textual acessível de administração (`data-labels-admin-search`) e dropdown de administração (`data-labels-admin-select`) com aria-live polite na interface de etiquetas.
- Garantir coerência e consistência visual e funcional com as demais telas do sistema.
