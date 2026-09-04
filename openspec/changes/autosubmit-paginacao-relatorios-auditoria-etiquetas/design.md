# autosubmit-paginacao-relatorios-auditoria-etiquetas Design

## Context
O padrão correto de autosubmit já existe em `resources/views/partials/filter-autosubmit.blade.php` e nas views de produtos (`products/index.blade.php` e `products/verification.blade.php`): `resetPage()` percorre `form.querySelectorAll('[name="page"],[name="pagina"]')`, limpa `value` e define `disabled = true` antes de `requestSubmit()`, e o script também define `lastSignature` e `aria-busy`. Relatórios, auditoria e etiquetas divergem: não chamam `resetPage()` ou usam `window.history.replaceState` (etiquetas), que não impede o `FormData` de levar `page` existente.

## Goals / Non-Goals
- Goals: alinhar as três views ao padrão de produtos/partial; garantir que mudança de filtro com autosubmit sempre volte à página 1.
- Non-Goals: paginação servidor, novos componentes, mudança de rotas.

## Decisions
- Adicionar `const resetPage = () => { ['page','pagina'].forEach(n => form.querySelectorAll(`[name=\"${n}\"]`).forEach(f => { f.value=''; f.disabled=true; })); }` e chamar `resetPage()` dentro de `submit*IfChanged` antes de marcar `dataset` e submeter.
- Em `labels/index.blade.php`, remover `const resetPage` baseado em `URL`/`history.replaceState` e substituir pelo padrão de formulário.
- Manter assinatura (`getSignature`/`lastSignature`) e `aria-busy`/`disabled` do botão como no padrão de produtos.

## Risks / Trade-offs
- Baixo risco; JS puro, sem impacto em backend. O caso de não haver campo `page` no formulário é tolerado (querySelectorAll vazio).
- Se futuramente houver campo hidden `page`, ele será corretamente limpo/desabilitado.

## Migration Plan
- Nenhuma migração necessária.

## Open Questions
- Nenhuma.
