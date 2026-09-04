## Context

O layout administrativo compartilhado concentra cabeçalho, navegação, ações rápidas e conteúdo de várias telas. Os controles já têm dimensões adequadas para toque, mas o foco geral de links, botões e campos não é padronizado; o novo contrato está em `specs/navegacao-acessivel/spec.md`.

## Goals / Non-Goals

**Goals:**

- Permitir que usuários de teclado pulem o cabeçalho e alcancem o conteúdo principal.
- Criar um indicador `focus-visible` único, legível nos temas claro e escuro.
- Manter o comportamento visual de mouse/toque e não adicionar dependências ou JavaScript para o caminho essencial.

**Non-Goals:**

- Reestruturar a navegação, alterar autenticação ou revisar a semântica específica de cada tela.
- Substituir a auditoria completa de acessibilidade ou corrigir componentes fora do layout administrativo compartilhado.

## Decisions

### Link de salto nativo

Adicionar o link antes do cabeçalho e um `id` no elemento `main`, usando posicionamento fora da tela no estado padrão e revelação em `:focus`. Essa solução funciona sem JavaScript, mantém a ordem de tabulação curta e evita duplicar o menu móvel. Um botão controlado por script foi descartado por introduzir estado desnecessário para uma ação de navegação simples.

### Foco por `:focus-visible`

Aplicar o anel a `a`, `button`, `input`, `select` e `textarea` com `:focus-visible`, preservando o foco nativo para navegadores que não suportam o pseudoestado. O anel usa duas camadas de sombra (contraste contra superfícies claras e escuras) sem deslocar o layout. Estilos dispersos de `outline: none` só serão alterados onde o layout compartilhado já define o foco do controle, mantendo componentes de relatório isolados fora deste escopo.

### Movimento e responsividade

O link de salto terá transições desnecessárias evitadas e o anel não dependerá de animação. O posicionamento usará a área segura do topo e manterá alvo mínimo de toque, enquanto a ordem existente do conteúdo não muda em telas menores.

## Risks / Trade-offs

- [Risco] O anel global pode chamar atenção em controles já estilizados → [Mitigação] limitar a regra a `:focus-visible`, usar tokens do tema e preservar estados específicos de checkbox.
- [Risco] O link de salto pode sobrepor o topo em viewport estreita → [Mitigação] posicioná-lo com `inset-block-start` seguro e validar a renderização mobile por teste de view.
- [Risco] Navegadores antigos ignoram `:focus-visible` → [Mitigação] deixar o estilo nativo de foco como fallback e não remover a acessibilidade do elemento.

## Migration Plan

A alteração é compatível e será publicada junto com o layout compartilhado. O rollback consiste em reverter o commit da melhoria caso a renderização de alguma tela revele sobreposição; não há migração de banco, cache persistente ou alteração de rota.
