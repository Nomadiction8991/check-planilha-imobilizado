# Design: filtro-etiquetas-busca-igreja

## Context

A tela de etiquetas é usada após verificação para gerar códigos de etiquetas por igreja/dependência. Hoje o filtro de igreja é um select simples sem busca; com muitas igrejas a seleção é lenta, igual ao problema já resolvido em produtos/relatórios.

## Goals / Non-Goals

- Goals: busca client-side sem round-trip; preservar name `comum_id` e GET; acessibilidade (label, aria-controls, status).
- Non-Goals: paginação server-side de igrejas, ordenação nova, alteração de escopo de consulta.

## Decisions

- Reutilizar o script inline leve já validado (querySelector + hidden/disabled por opção; status com role=status).
- Não extrair para asset compartilhado nesta mudança para manter diff mínimo e isolado (mesma decisão dos changes anteriores).
- Remover `onchange="this.form.submit()"` do select de igreja nesta view: com busca filtrável, submit automático atrapalha a digitação; o usuário confirma com Filtrar (comportamento igual aos filtros de relatórios/produtos).

## Risks / Trade-offs

- Duplicação de JS entre views — mitigado por padrão pequeno e isolado; extração futura pode unificar se necessário.

## Migration Plan

- Sem migração. Rollback: remover input e script da view e recolocar onchange se desejado.

## Open Questions

- Nenhuma.
