## Context

A tela de usuários usa `onclick` inline para confirmar exclusão. O layout de migração já concentra scripts globais e recebe todos os formulários submetidos nas telas administrativas.

## Goals / Non-Goals

**Goals:**

- Declarar confirmação no HTML com atributo `data-confirm`.
- Interceptar somente formulários marcados e manter envio após confirmação.
- Preservar acessibilidade e compatibilidade com touch e teclado.

**Non-Goals:**

- Alterar regras de autorização, rotas ou persistência.
- Criar modal customizado ou dependência JavaScript.

## Decisions

Usar delegação de evento `submit` no layout compartilhado, em vez de scripts duplicados por tela. O navegador fornece confirmação nativa, suficiente para ação destrutiva e compatível com os fluxos atuais. A mensagem fica no atributo para permanecer específica por ação e permitir reutilização em outras telas.

Alternativa descartada: manter `onclick`, porque mistura comportamento com marcação e não escala para outros formulários.

## Risks / Trade-offs

- [Mensagem nativa varia por navegador] → manter texto curto, explícito e específico.
- [JavaScript indisponível] → formulário segue enviável, sem bloquear usuários; proteção de autorização permanece no servidor.

## Migration Plan

Adicionar atributo ao formulário de exclusão de usuários e listener global no layout. Validar com teste de renderização, lint PHP, suíte PHPUnit e sondagem da aplicação. Rollback consiste em remover atributo e listener.

## Open Questions

Nenhuma.
