## Context

Consulte `proposal.md` para a motivação. As duas telas de produtos já compartilham a mesma estrutura de filtros, mas mantêm lógica de busca client-side duplicada para administração e igreja. O formulário GET existente é a fonte de verdade e o servidor já normaliza todos os filtros, portanto a melhoria deve apenas orquestrar o envio no navegador.

## Goals / Non-Goals

**Goals:**

- Reduzir toques e espera para aplicar filtros usados durante a conferência.
- Aplicar a mesma política de submissão automática nas telas de listagem e verificação.
- Evitar uma requisição por tecla e cancelar timers anteriores quando o usuário continua digitando.
- Preservar a submissão manual, navegação por teclado e feedback acessível.
- Não alterar a consulta, a autorização, o escopo administrativo ou os parâmetros aceitos pelo backend.

**Non-Goals:**

- Não transformar os filtros em uma busca AJAX parcial.
- Não submeter automaticamente o campo de busca de administração ou igreja enquanto ele filtra opções locais.
- Não remover o botão "Filtrar" nem alterar paginação, chips ou links de retorno.

## Decisions

1. **Submissão nativa do formulário, em vez de fetch.** O JavaScript chamará `requestSubmit()` no formulário existente. Isso mantém validações e comportamento GET do navegador, permite deep links e evita duplicar a montagem de query no frontend. Um `submit` disparado pelo usuário ou por `requestSubmit()` passará pela mesma guarda de estado.

2. **Eventos automáticos somente nos controles que alteram o resultado.** Selects de administração, igreja, estado, dependência, tipo e status disparam após `change`. A busca geral usa `input` com debounce curto e dispara imediatamente no evento `search`, cobrindo o botão nativo de limpar. Os campos de busca local de administração e igreja ficam fora dessa rotina porque não representam parâmetros enviados ao servidor.

3. **Debounce e deduplicação por assinatura.** Cada formulário terá um timer próprio e uma assinatura serializada dos valores submetíveis. Mudanças que não alteram a assinatura não geram nova navegação. O envio automático limpa o timer pendente, marca o formulário como enviando e mostra um estado textual no botão sem remover sua função.

4. **Feedback acessível e preservação manual.** O formulário terá uma região `role="status"` com `aria-live="polite"`, atualizada apenas durante a navegação automática. O botão continua com rótulo "Filtrar" e o envio manual é respeitado; o listener de `submit` somente cancela timers e impede uma segunda submissão automática agendada.

5. **Implementação compartilhável dentro de cada página.** Como as views são Blade server-rendered e não há bundle específico para os filtros, será usado um pequeno inicializador vanilla JavaScript incluído nas duas telas. A rotina será idempotente por formulário e funcionará mesmo quando apenas parte dos controles estiver presente em testes ou em uma variação legada.

## Risks / Trade-offs

- **[Risco]** Um usuário pode alterar vários selects rapidamente e provocar navegações sucessivas. **Mitigação:** `change` é consolidado por um timer de 80 ms, com cancelamento do timer anterior e assinatura para ignorar estados idênticos.
- **[Risco]** A busca pode navegar antes de o usuário terminar uma palavra. **Mitigação:** debounce de 350 ms, evento `change` para confirmação e evento `search` para limpeza/Enter.
- **[Risco]** Alguns navegadores antigos podem não implementar `requestSubmit()`. **Mitigação:** fallback para `form.submit()` somente quando o método nativo não existir, preservando a ação GET.
- **[Risco]** Feedback visual pode causar deslocamento de layout. **Mitigação:** reservar a região de status no próprio bloco de ações e alterar apenas texto/atributos, sem animar dimensões.

## Migration Plan

Nenhuma migração de dados ou dependência é necessária. O deploy é reversível por rollback do commit; em caso de regressão, a remoção do inicializador devolve o envio exclusivamente manual, sem impacto no backend.
