## Context

Filtros da auditoria chegam como strings de query e serviço atual ignora datas inválidas. Validação deve ocorrer antes da consulta e servir tanto tela quanto exportação.

## Goals / Non-Goals

**Goals:**

- Rejeitar datas fora do formato Y-m-d.
- Rejeitar período invertido.
- Preservar filtros e comunicar correção necessária.

**Non-Goals:**

- Alterar armazenamento ou formato do CSV.
- Criar novo componente de validação compartilhado fora da auditoria.

## Decisions

Validar no controlador com regras de request Laravel `date_format:Y-m-d` e comparação após parse. Isso impede consulta e exportação sem duplicar lógica no serviço. Datas vazias continuam permitidas. Mensagens ficam em português e ligadas aos campos para a tela exibir erro contextual.

Alternativa rejeitada: deixar serviço ignorar datas inválidas, pois produz resultado silenciosamente incorreto.

## Risks / Trade-offs

- [Risco] Clientes legados podem enviar data fora do formato HTML date. → [Mitigação] Retornar erro preservando query e informar formato esperado.
- [Risco] Validação duplicada entre endpoints. → [Mitigação] Usar método privado comum no controlador.

## Migration Plan

Nenhuma migração. Deploy normal; rollback por reversão do código caso necessário.
