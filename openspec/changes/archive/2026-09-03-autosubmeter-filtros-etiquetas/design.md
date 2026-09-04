## Context

A tela de etiquetas já possui um formulário GET com filtros de administração, estado, igreja e dependência. As buscas textuais de administração e igreja são apenas filtros locais de opções; os demais controles representam critérios enviados ao servidor. O formulário é renderizado em uma única view e usa a mesma navegação tradicional das demais telas do sistema.

## Goals / Non-Goals

**Goals:**

- Atualizar a tela de etiquetas imediatamente depois que um filtro enviado ao servidor mudar.
- Evitar navegações enquanto o usuário ainda está digitando nas buscas locais.
- Debouncear a busca geral de administração e igreja, mantendo o botão nativo de limpeza responsivo.
- Evitar submissões duplicadas quando o valor efetivo do formulário não mudou.
- Reiniciar a página ao aplicar filtros novos e preservar o envio manual.
- Exibir feedback acessível durante a navegação automática.

**Non-Goals:**

- Alterar as regras de escopo ou as consultas de etiquetas.
- Criar endpoint assíncrono ou dependência JavaScript.
- Persistir o texto das buscas locais na URL.
- Automatizar a inclusão ou remoção de etiquetas manuais.

## Decisions

1. **Reaproveitar o padrão de submissão automática das telas de produtos.** A rotina será colocada no formulário de etiquetas com data attribute próprio, usando eventos `change` nos selects enviados e `input`, `search` e `change` na busca geral. Isso mantém a experiência consistente sem introduzir outra abstração para uma única tela.

2. **Manter buscas de administração e igreja como filtros locais.** Esses campos só ocultam opções no DOM e não possuem `name`; portanto, não devem causar navegação durante a digitação. O select associado dispara a atualização quando a opção efetiva é escolhida ou quando a limpeza local remove uma escolha incompatível.

3. **Usar assinatura serializada e timer por formulário.** A assinatura será derivada dos controles submetíveis com `FormData`, incluindo a página se presente apenas para detectar a mudança, mas o envio automático removerá o parâmetro `page`/`pagina` da URL por meio de um campo oculto temporário ou navegação normal equivalente. Assim, uma alteração de filtro não mantém o usuário em uma página vazia.

4. **Feedback textual sem bloquear o caminho manual.** A região já existente de status receberá uma mensagem curta e `aria-live="polite"`; o botão continuará disponível e o listener de `submit` apenas limpará timers pendentes e marcará o formulário como em processamento.

5. **Falha de compatibilidade tratada com fallback nativo.** Em navegadores sem `requestSubmit`, a rotina usará `form.submit()`. A melhoria não depende de APIs modernas além das já utilizadas pelo layout.

## Risks / Trade-offs

- **Nova navegação a cada select alterado** → comportamento intencional para manter os códigos sincronizados; os selects são controles discretos e o feedback indica a atualização.
- **Busca local em uma lista extensa** → permanece client-side e debounceada, evitando chamadas enquanto o usuário digita.
- **Usuário perde a página atual ao mudar filtro** → desejável: o novo conjunto começa no início, evitando estado vazio ou resultados inesperados.
- **Falha de rede ou retorno inválido** → a navegação GET segue o comportamento existente do servidor; nenhuma atualização parcial é aplicada no navegador.

## Migration Plan

Nenhuma migração de dados ou configuração é necessária. A alteração será publicada junto com a view e os testes de renderização. O rollback consiste em remover o comportamento automático da view, mantendo o formulário manual intacto.
