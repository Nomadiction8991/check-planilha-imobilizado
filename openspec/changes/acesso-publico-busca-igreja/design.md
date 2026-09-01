# Design: acesso-publico-busca-igreja

## Context

`resources/views/public-access/create.blade.php` hoje renderiza um `select#comum_id` com todas as igrejas sem filtro. A lista vem de `PublicAccessController@create` via `Comum::query()->get()`. Não há categorização ou paginação no fluxo público; o objetivo é apenas filtrar no cliente.

## Goals / Non-Goals

**Goals:**
- Busca client-side rápida e acessível sem nova rota ou query adicional.
- Preservar validação e redirecionamento existentes.

**Non-Goals:**
- Autocomplete remoto, paginação servidor ou ordenação nova.
- Alterar sessão/contratos de `PublicAccessController@store`.

## Decisions

- **Filtro no cliente sobre options existentes** em vez de endpoint dedicado: evita latência e mantém compatibilidade; lista pública é limitada e já vem completa na view.
  - Alternativa considerada: endpoint JSON com debounce — descartada por complexidade desnecessária nesta etapa.
- **Ocultar options não correspondentes via atributo `hidden`/`disabled` em vez de remover do DOM**: preserva valor selecionado e validação nativa do select; restaura facilmente ao limpar.
  - Alternativa: reconstruir select via JS — mais frágil com validação e acessibilidade.
- **Campo `input[type=search]` com label e `aria-controls`**: melhora descoberta e leitores de tela; mensagem de "nenhum resultado" em região com `role=status` e `aria-live=polite`.

## Risks / Trade-offs

- Lista muito grande pode ter scroll longo → Mitigação: filtro reduz lista rapidamente; se escalar, evoluir para autocomplete remoto em change futuro.
- Termo com acentos vs sem acentos → Mitigação: normalização por `toLowerCase` + `normalize('NFD').replace(/[\u0300-\u036f]/g,'')` se necessário em iteração futura (esta entrega usa substring simples).
