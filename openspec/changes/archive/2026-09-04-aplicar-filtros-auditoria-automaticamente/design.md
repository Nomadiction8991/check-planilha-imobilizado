## Context

A view de auditoria já possui formulário GET com filtros server-side e um estado acessível para mensagens, mas ainda só envia os dados quando o botão é acionado. As listas de produtos e relatórios já usam o padrão de assinatura do formulário, agendamento e `requestSubmit`, que deve ser adaptado sem alterar o contrato HTTP.

## Goals / Non-Goals

**Goals:**

- Reutilizar o padrão de submissão automática já adotado nas telas principais.
- Reagir imediatamente a selects e datas, e com debounce à busca geral.
- Evitar submissões para valores inalterados e manter feedback acessível.
- Preservar o caminho manual para navegadores sem JavaScript ou preferência do usuário.

**Non-Goals:**

- Alterar critérios de consulta, paginação, autorização ou exportação.
- Criar endpoint assíncrono ou dependência frontend nova.
- Filtrar administrações ou substituir a busca assistida existente.

## Decisions

### Submissão GET nativa com `FormData`

A view usará a assinatura serializada do próprio formulário para detectar mudanças e `requestSubmit()` para manter validação e comportamento nativo. Isso evita duplicar nomes de parâmetros e mantém compatibilidade com o controller existente. Um `fetch` foi rejeitado porque exigiria reconstruir a renderização completa da tabela e seus estados de erro.

### Debounce somente na busca geral

Selects e datas serão enviados após um pequeno intervalo de estabilidade, enquanto a busca textual aguardará uma pausa maior. Assim, a seleção de um filtro parece imediata sem causar navegações sucessivas ao digitar.

### Feedback no próprio formulário

O status `aria-live` já presente receberá uma mensagem durante o envio, e o botão ficará ocupado/desabilitado para prevenir duplo clique. O texto será específico à auditoria para não confundir o usuário com mensagens de outras telas.

## Risks / Trade-offs

- [Risco] Usuários podem preferir preencher vários filtros antes de navegar → [Mitigação] o botão manual permanece disponível; somente a mudança de campo inicia a atualização automática.
- [Risco] Submissão automática pode perder uma seleção feita durante o intervalo → [Mitigação] cancelar o agendamento anterior, comparar a assinatura imediatamente antes do envio e desabilitar o botão durante a submissão.
- [Risco] JavaScript indisponível → [Mitigação] formulário GET continua funcional sem o script.

## Migration Plan

Nenhuma migração de dados ou configuração é necessária. Após publicar a view, validar o comportamento com teste de página e suíte do projeto; em caso de regressão, remover o comportamento automático mantendo o formulário original.
