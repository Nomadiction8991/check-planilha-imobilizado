# Design: Sanitização de CSV nos formulários de relatório

## Context

Ver proposal.md e deltas em specs/. Os CSVs de relatório usam `renderCsv` e métodos específicos (`downloadVerificationBackupCsv`, `downloadPositionCsv`) com `fputcsv` direto. Campos textuais (descrição, dependência, observação, fornecedor) vêm do cadastro e podem conter prefixos perigosos.

## Goals / Non-Goals

**Goals:**
- Neutralizar fórmula em todos os exports CSV de relatório.
- Reusar a mesma regra da auditoria (prefixo `'` quando primeiro byte é `=+-@\t\r`).
- Cobrir com testes unitários.

**Non-Goals:**
- Sanitizar na persistência/entrada.
- Alterar formato de colunas ou separador.

## Decisions

- Adicionar método privado `sanitizeCsvText` em `LegacyReportService` (mesma lógica de `LegacyAuditTrailService`) e aplicar apenas a campos textuais nos três writers. Valores sistêmicos (código, data, status, flags 0/1) não são sanitizados.
- Alternativa descartada: sanitizar em `renderCsv` genérico para tudo — poluiria códigos/datas e exigiria lista de exceções por coluna.

## Risks / Trade-offs

- [Apóstrofo visível em texto perigoso] → Trade-off aceito; só em células de risco.
- [Novo writer esquecido] → Mitigar com teste que verifica sanitização em cada export.

## Migration Plan

Sem migração de banco. Deploy normal; rollback é reverter o commit.
