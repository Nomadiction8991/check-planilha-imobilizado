## Context

A exportação vazia do controlador recebe filtros com chaves internas (`search`, `module`, `date_from`, `date_to`), enquanto a rota e a tela usam parâmetros públicos em português (`busca`, `modulo`, `data_inicio`, `data_fim`). O redirecionamento atual passa as chaves internas diretamente para a URL.

## Goals / Non-Goals

**Goals:**

- Fazer retorno sem resultados manter exatamente filtros públicos informados.
- Evitar duplicação de regras ou alteração no serviço de auditoria.

**Non-Goals:**

- Alterar consulta, ordenação, formato CSV, autenticação ou permissões.

## Decisions

- Montar mapa explícito entre filtros internos e parâmetros públicos no ponto de redirecionamento. Isso mantém o contrato interno do serviço separado da interface HTTP e evita expor nomes de implementação.
- Remover valores vazios antes de redirecionar, preservando comportamento limpo da URL.
- Adicionar expectativa de URL completa no teste de retorno vazio, cobrindo todos os filtros.

## Risks / Trade-offs

- [Risco] Novo filtro pode ser esquecido no mapa. → [Mitigação] Teste usa busca, módulo e duas datas, cobrindo conjunto atual de filtros.
- [Risco] Mudança visual não refletir filtros. → [Mitigação] Parâmetros seguem nomes já consumidos pelo método `index`.

## Migration Plan

Nenhuma migração de banco. Alteração entra no deploy normal da aplicação. Rollback consiste em reverter commit caso necessário.

## Open Questions

Nenhuma.
