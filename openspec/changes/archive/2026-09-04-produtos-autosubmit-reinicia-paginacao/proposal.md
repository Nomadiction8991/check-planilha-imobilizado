## Why

Na listagem e verificação de produtos, a submissão automática de filtros pode manter o parâmetro de paginação anterior. Se o usuário estava na página 3 e altera um filtro que reduz o resultado para poucas páginas, a navegação permanece em página agora inexistente e exibe resultado vazio.

## What Changes

- Ao submeter filtros automaticamente por mudança de selects ou busca nos formulários de produtos, limpar e desabilitar campos de paginação (`page`/`pagina`) antes do submit.
- Manter submissão manual compatível quando o usuário clica em Filtrar.
- Aplicar o mesmo comportamento nas duas telas: `/products` e `/products/verification`.

## Capabilities

### New Capabilities

- Nenhuma.

### Modified Capabilities

- `produtos-listagem`: atualização automática de filtros passa a reiniciar a paginação para a primeira página.

## Impact

Afeta `resources/views/products/index.blade.php` e `resources/views/products/verification.blade.php` (JavaScript de autosubmit). Não altera contratos HTTP, paginação servidor ou escopo administrativo; apenas garante que o GET enviado após mudança de filtro não carregue página obsoleta.
