## Context

Consulte `proposal.md` para a motivação. As cinco telas usam formulários GET independentes, com campos de filtro server-side e algumas buscas auxiliares que apenas reduzem opções de selects no navegador. As telas de produtos, relatórios, etiquetas e auditoria já demonstram o padrão visual e comportamental esperado para submissão automática.

## Goals / Non-Goals

**Goals:**

- Aplicar o mesmo comportamento de atualização automática nas cinco listagens sem duplicar rotas ou lógica de servidor.
- Identificar explicitamente os campos que participam da consulta e excluir as buscas auxiliares.
- Resetar a paginação quando uma alteração de filtro puder tornar a página atual inválida.
- Preservar acessibilidade, envio manual e compatibilidade com navegadores sem `requestSubmit`.

**Non-Goals:**

- Alterar DTOs, controllers, serviços, consultas ou contratos HTTP existentes.
- Transformar as buscas auxiliares de selects em filtros server-side.
- Criar chamadas AJAX ou substituir a navegação GET por atualização parcial.

## Decisions

1. **Usar atributos declarativos por tela.** Cada formulário terá um marcador próprio e cada controle enviado ao servidor será marcado individualmente. Isso mantém o escopo explícito, evita capturar campos auxiliares e permite que cada view tenha uma rotina pequena, legível e testável.

2. **Submeter pela navegação GET existente.** A rotina usará `requestSubmit()` quando disponível e `form.submit()` como fallback. Assim, validações e links de paginação continuam usando o contrato já consumido pelo backend, sem introduzir dependência ou estado AJAX.

3. **Debounce para buscas e atraso curto para selects.** A alteração de selects aguardará 80 ms para absorver mudanças encadeadas; a busca textual aguardará 350 ms e tratará o evento `search` imediatamente para cobrir a limpeza nativa. Um timer por formulário cancela o trabalho anterior.

4. **Assinatura e paginação.** A assinatura serializada dos controles submetíveis impede submissões repetidas. Nas telas paginadas, a rotina removerá `page` e `pagina` da URL antes da nova navegação, evitando que um filtro novo preserve uma página sem resultados.

5. **Feedback sem bloquear o caminho manual.** O status `role="status"`/`aria-live="polite"` receberá uma mensagem durante a navegação automática; o botão continuará visível e o listener de `submit` cancelará timers pendentes para impedir duplicidade.

6. **Inicialização idempotente.** Um marcador em cada formulário evitará instalação duplicada se a view for reutilizada ou incorporada, sem depender de um bundle global novo.

## Risks / Trade-offs

- **[Busca textual dispara navegação após pausa]** → O debounce de 350 ms reduz navegações durante digitação contínua; a limpeza nativa usa evento próprio para não deixar resultados antigos na tela.
- **[Filtro muda enquanto a página atual está alta]** → A página é removida antes da submissão automática nas listagens paginadas.
- **[Scripts repetidos nas views]** → O uso de atributos e marcadores idempotentes mantém o comportamento isolado, enquanto os testes verificam que buscas auxiliares não entram na rotina.
- **[Navegador sem requestSubmit]** → Fallback para `form.submit()` preserva compatibilidade, sem impedir o envio GET.

## Migration Plan

Não há migração de dados nem mudança de rota. Após a implementação, validar os artefatos OpenSpec, executar os testes de apresentação e a suíte do projeto, verificar a sintaxe dos arquivos PHP alterados e confirmar as rotas de listagem em runtime. O rollback consiste em reverter a alteração das views, sem impacto em dados persistidos.
