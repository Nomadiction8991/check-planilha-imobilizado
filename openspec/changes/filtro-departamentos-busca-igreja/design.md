# Design: filtro-departamentos-busca-igreja

## Context

A listagem de dependências é base para vincular produtos a igrejas e hoje repete o padrão antigo de select único sem busca. Consistência com produtos/relatórios reduz curva de aprendizado e prepara ampliação para outros filtros.

## Goals / Non-Goals

- Goals: busca client-side sem round-trip; preservar name `comum_id` e GET; acessibilidade (label, aria-controls, status).
- Non-Goals: paginação server-side de igrejas, ordenação nova, alteração de escopo de consulta.

## Decisions

- Reutilizar o script inline leve já validado (querySelector + hidden/disabled por opção; status com role=status).
- Não extrair para asset compartilhado nesta mudança para manter diff mínimo e isolado (mesma decisão dos changes anteriores).
- Manter `comum_id` como único select filtrado; outros filtros permanecem sem busca (dependência tem busca textual própria).

## Risks / Trade-offs

- Duplicação de JS entre views — mitigado por padrão pequeno e isolado; extração futura pode unificar se necessário.

## Migration Plan

- Sem migração. Rollback: remover input e script da view.

## Open Questions

- Nenhuma.
