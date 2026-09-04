## Context

As duas views de produtos mantêm a mesma lógica de filtros locais em blocos JavaScript duplicados. A busca local altera apenas `hidden` e `disabled` das opções, enquanto a submissão automática observa os selects do formulário. O ajuste deve respeitar esse desenho existente e funcionar também quando o navegador dispara o evento `search` nativo ao limpar um input de busca.

## Goals / Non-Goals

**Goals:**

- Centralizar a decisão de seleção válida no ciclo de filtragem local de administração e igreja.
- Limpar o select somente quando a opção selecionada estiver oculta pelo termo atual.
- Disparar uma nova consulta após a limpeza quando a assinatura dos campos submetidos tiver mudado.
- Manter o feedback acessível de ausência de resultados e a disponibilidade do placeholder.

**Non-Goals:**

- Alterar filtros, rotas ou consultas no backend.
- Submeter o texto das buscas auxiliares ao servidor.
- Reestruturar os scripts compartilhados do layout nesta melhoria.

## Decisions

1. **Reutilizar o fluxo de atualização já existente.** A função de filtragem local continuará responsável por visibilidade, estado habilitado e mensagem. Quando precisar limpar uma seleção, ela acionará o evento `change` do select para passar pelo debounce e pela deduplicação já usados pelos filtros do servidor. Alternativa rejeitada: chamar `form.submit()` diretamente, pois isso ignora a assinatura e o estado de feedback.

2. **Limpar somente seleção ocultada.** Uma seleção visível não será alterada enquanto o usuário digita ou limpa o campo auxiliar. Se a opção selecionada estiver oculta, o valor voltará ao placeholder; o evento de mudança ocorrerá apenas quando houver efetiva alteração. Alternativa rejeitada: zerar sempre o select ao limpar a busca, pois isso descarta uma escolha válida sem necessidade.

3. **Aplicar a mesma regra nas duas telas.** Os scripts duplicados das views receberão comportamento equivalente, preservando seus seletores específicos e evitando uma alteração mais ampla no layout base. Isso reduz risco de regressão em outras telas que não possuem esses filtros.

4. **Manter acessibilidade existente.** Ao restaurar opções, o placeholder continuará disponível; quando não houver correspondências, o select permanecerá desabilitado e o status continuará anunciando a ausência. O acionamento da submissão não dependerá de hover ou interação exclusiva por mouse.

## Risks / Trade-offs

- [Risco] O disparo de `change` pode iniciar navegação durante a limpeza do campo auxiliar. → [Mitigação] O listener existente compara a assinatura dos campos submetidos e não envia requisição quando o valor já estiver vazio.
- [Risco] A duplicação entre as views pode divergir no futuro. → [Mitigação] Cobrir os dois marcadores no teste de view e manter a regra textual equivalente durante esta mudança.
- [Risco] Navegadores antigos podem não implementar o evento `search`. → [Mitigação] O comportamento também será conectado ao evento `input`, que já cobre a limpeza por teclado e por controles compatíveis.

## Migration Plan

Nenhuma migração de dados é necessária. Após a publicação, a nova lógica será carregada junto com as views; em caso de regressão, basta reverter a alteração das views sem impacto persistente.
