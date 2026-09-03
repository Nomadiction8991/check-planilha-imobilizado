# Chips de filtros ativos em produtos

## Why

As telas de produtos (`/products` e `/products/verification`) já oferecem vários filtros (administração, igreja, estado, dependência, tipo de bem, status, busca). Quando vários estão ativos, o usuário precisa rolar até o topo para lembrar o que filtrou ou para remover um critério específico sem limpar tudo — especialmente no mobile.

## What Changes

- Exibir, abaixo dos filtros, uma barra de chips que resume apenas os filtros ativos no momento, com rótulos legíveis (ex.: "Administração: Central", "Igreja: 12-3456 - Central", "Estado: SP", "Status: Com nota", …).
- Cada chip traz um botão de remoção (×) que recarrega a página preservando os demais filtros ativos.
- Quando nenhum filtro estiver ativo, a barra não aparece — evita ruído visual.
- Mantém o botão "Limpar" existente como atalho para remover todos; chips são o complemento fino para remoção seletiva.
- Acessibilidade: barra com `aria-live="polite"` e botões com `aria-label` descrevendo o filtro removido.

## Capabilities

### New Capabilities

- `produtos-chips-filtros`: resumo visual de filtros ativos em listagem/verificação de produtos, com remoção seletiva por chip.

### Modified Capabilities

- (nenhuma — comportamento de filtragem/paginação no servidor não muda; apenas apresentação)

## Impact

- Views: `resources/views/products/index.blade.php`, `resources/views/products/verification.blade.php`.
- Controller já expõe `filters`, `churches`, `administrations`, `dependencies`, `assetTypes`, `states` e `statusOptions` — chips resolvem rótulos a partir desses dados, sem mudança de rota/serviço.
- Sem migração, sem nova dependência.
