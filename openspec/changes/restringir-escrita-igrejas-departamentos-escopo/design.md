## Context

Ver S/ `proposal.md`. Leitura já filtra por escopo, mas a escrita de igrejas (`LegacyChurchManagementService::update`) e de dependências (`LegacyDepartmentManagementService::create/update/delete`) ainda não verifica a administração/igreja contra a sessão do usuário. Produtos já usam o trait `ResolvesLegacyProductScope` com `assertChurchWithinProductScope` (sessão `is_admin` / `administracao_id` / `administracoes_permitidas`). A mesma ideia será estendida, com checagem adicional de administração alvo na edição de igrejas.

## Goals / Non-Goals

**Goals:**

- Bloquear no backend, antes de qualquer mutação, operações sobre igrejas/dependências fora do escopo.
- Reutilizar a fonte de escopo de produtos; validar administração alvo ao mover igreja.
- Manter compatibilidade: administradores globais bypassam; sessões sem escopo explícito não ganham permissão extra.
- Preservar mensagens atuais de negócio e contrato de erro (redirect com `status`/`status_type=error`).

**Non-Goals:**

- Alterar schema, migrations ou modelo de permissões.
- Mudar UX/filtros visuais além das mensagens de erro.
- Bloquear leitura (já coberta pelos browsers).

## Decisions

### Estender `ResolvesLegacyProductScope` para administração

O trait ganhará `assertAdministrationWithinScope(int $administrationId)` que reaproveita `productScopeIsGlobal()` / `productScopeAdministrationIds()` e lança mensagem `A administração selecionada está fora do seu escopo permitido.` quando fora. Igreja já possui `assertChurchWithinProductScope`. Isso evita duplicar lógica de sessão e mantém um único ponto de verdade.

Alternativa considerada: duplicar verificação inline nos dois services. Rejeitada por duplicar parsing de sessão e divergir da regra de produtos.

### Ordem de validação

- **Igrejas (`update`)**: validar primeiro a igreja atual (`assertChurchWithinProductScope` na igreja existente) e depois a nova administração (`assertAdministrationWithinScope` no `administrationId` do DTO), antes de qualquer `fill/save`. Assim troca de administração para fora do escopo é barrada mesmo que a igreja atual esteja dentro.
- **Dependências (`create`)**: validar a igreja alvo antes de checar existência/unicidade.
- **Dependências (`update`)**: validar a dependência atual (igreja da dependência existente) e depois a nova igreja, antes de unicidade e `fill/save`.
- **Dependências (`delete`)**: validar a igreja atual antes de checar vínculo com produtos.

Alternativa considerada: validar só a nova igreja. Rejeitada porque permitiria editar/excluir registro que o usuário nem deveria enxergar.

### Sem mudança em controllers

Controllers continuam capturando `RuntimeException` e retornando redirect com `status`/`status_type=error`, como já fazem. A proteção vive no service, cobrindo chamadas diretas.

## Risks / Trade-offs

- [Risco] `administracao_id` nulo/zero na igreja legada pode escapar da checagem. → [Mitigação] `assertChurchWithinProductScope` já trata `administracao_id <= 0` como fora do escopo quando não global.
- [Risco] Admin restrito com sessão incompleta (sem `administracoes_permitidas`) fica sem escopo e perde escrita. → [Mitigação] Comportamento desejado de fail-closed; `productScopeAdministrationIds` já considera `administracao_id` fallback.
- [Risco] Mensagem nova divergir de testes existentes que esperam sucesso. → [Mitigação] Testes existentes permanecem verdes (são admin/mock); novos testes cobrem bloqueio sem mutação.

## Migration Plan

Sem migração. Deploy: `git push` na main dispara runner. Rollback: reverter commit. Validar pós-deploy com `curl /login` e um teste manual de edição de igreja/dependência com usuário restrito.
