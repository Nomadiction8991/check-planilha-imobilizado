# Filtros colapsáveis em produtos no mobile

## Why

Nas telas de produtos (`/products` e `/products/verification`) o bloco de filtros ocupa grande altura no mobile, empurrando a lista de resultados para fora da primeira dobra. Quem consulta pelo celular precisa rolar muito só para ver os produtos, mesmo quando já filtrou. Um contêiner colapsável recolhe os filtros por padrão no mobile e mantém o acesso rápido, melhorando a experiência mobile-first sem afetar o desktop.

## What Changes

- Envolver o formulário de filtros + chips em um contêiner colapsável com botão de alternância visível apenas no mobile (`≤860px`), escondido no desktop.
- Botão mostra rótulo “Filtros” e contador de filtros ativos (ex.: “Filtros · 2 ativos”) e estado `aria-expanded`.
- No mobile, colapsado por padrão; ao expandir, revela todos os controles existentes (busca de administração/igreja, UF, busca geral, dependência, tipo, status, chips e ações Filtrar/Limpar).
- No desktop (`≥861px`) o comportamento permanece idêntico ao atual: filtros sempre visíveis, botão oculto.
- Sem mudança de rota, serviço ou contrato de `ProductFilters`; apenas apresentação e um pequeno script progressivo.

## Capabilities

### New Capabilities

- `produtos-filtros-colapsaveis`: filtros colapsáveis no mobile para listagem e verificação de produtos, com botão de alternância e contagem de filtros ativos.

### Modified Capabilities

- (nenhuma — filtragem/paginação no servidor não muda)

## Impact

- Views: `resources/views/products/index.blade.php`, `resources/views/products/verification.blade.php`.
- Layout/CSS/JS: `resources/views/layouts/migration.blade.php` (estilos do botão e do estado colapsado + script de toggle).
- Sem migração, sem nova dependência, sem alteração de controller/service.
