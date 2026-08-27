## Context

Ver proposal.md e deltas em specs/. A exportação usa `fputcsv` com dados textuais vindos do log de auditoria. Proteção precisa ocorrer no ponto de saída, sem alterar filtros nem persistência.

## Goals / Non-Goals

**Goals:**

- Neutralizar fórmula em campos textuais exportados.
- Cobrir serviço e endpoint com testes.
- Manter CSV, colunas e valores sistêmicos compatíveis.

**Non-Goals:**

- Alterar modelo do log.
- Sanitizar dados na entrada.
- Criar dependência externa.

## Decisions

Criar sanitização local e explícita no serviço de auditoria, aplicada apenas a campos textuais controlados por usuários. Prefixar com apóstrofo quando primeiro byte for caractere perigoso, padrão compatível com planilhas. Não sanitizar datas, IDs ou status HTTP, pois não são texto controlado e isso alteraria semântica de dados. Alternativa descartada: sanitizar somente importação, pois registros existentes e integrações também podem inserir valores perigosos.

## Risks / Trade-offs

- [Texto perigoso fica com apóstrofo visível ao usuário] → Trade-off necessário para impedir execução de fórmula; aplicar somente em células textuais de risco.
- [Novos campos podem ser adicionados sem proteção] → Manter teste de exportação cobrindo campos textuais e revisar qualquer novo writer CSV.

## Migration Plan

Nenhuma migração de banco. Deploy normal da aplicação; rollback por reversão do código caso necessário.
