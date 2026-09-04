## Context

Consulte `proposal.md` para a motivação. A tela de relatórios já usa um formulário GET para aplicar administração, estado e igreja, além de possuir buscas client-side separadas para localizar opções dentro dos selects. A mudança deve orquestrar apenas os controles enviados ao servidor, sem duplicar a construção da consulta.

## Goals / Non-Goals

**Goals:**

- Reduzir toques necessários para trocar filtros na seleção de relatórios.
- Preservar o formulário GET, seus parâmetros e a navegação por links diretos.
- Evitar submissões repetidas quando vários controles são alterados rapidamente.
- Manter busca local de administração e igreja independente da consulta do servidor.
- Oferecer feedback textual acessível e preservar o envio manual.

**Non-Goals:**

- Não transformar a listagem em uma atualização parcial via AJAX.
- Não alterar autorização, escopo das igrejas, serviços de relatórios ou contratos HTTP.
- Não submeter enquanto o usuário apenas digita nas buscas locais.

## Decisions

1. **Submissão nativa do formulário.** O inicializador chamará `requestSubmit()` no formulário existente, mantendo validação, método GET, deep links e montagem nativa dos parâmetros. Um fallback para `form.submit()` atenderá navegadores sem `requestSubmit()`.

2. **Eventos somente nos filtros enviados.** Selects com os nomes `administracao_id`, `estado` e `comum_id` dispararão após `change`. Os campos `data-reports-admin-search` e `data-reports-church-search` permanecerão fora da rotina porque servem apenas à filtragem visual de opções.

3. **Debounce curto e deduplicação.** Cada alteração agendará uma submissão com atraso de 80 ms, cancelando o timer anterior. Uma assinatura serializada do `FormData` impede navegação quando os valores não mudaram.

4. **Feedback sem deslocamento.** Ações manterão uma região `role="status"` com `aria-live="polite"`, atualizada com o texto de processamento. O botão continuará disponível para submissão manual, enquanto o listener de `submit` cancela timers pendentes.

5. **Inicializador idempotente.** Um marcador no formulário evitará instalação duplicada caso a view seja reutilizada. O código tolerará a ausência do botão ou da região de status sem impedir o envio.

## Risks / Trade-offs

- **[Risco]** Alterações sucessivas nos selects podem iniciar mais de uma navegação. **Mitigação:** timer único de 80 ms, cancelamento e assinatura dos valores.
- **[Risco]** Um navegador sem `requestSubmit()` pode não executar a mesma validação nativa. **Mitigação:** usar o método nativo quando disponível e fallback somente como compatibilidade.
- **[Risco]** O feedback pode ficar visível até a navegação terminar. **Mitigação:** manter a mensagem na área reservada; a nova renderização limpa o estado.

## Migration Plan

Nenhuma migração de dados ou dependência é necessária. O deploy é reversível pelo rollback do commit e não altera rotas, serviços ou dados persistidos.
