# Visual: Listagem de Produtos

## Descrição Estética:
- **Composição geral**: A tela começa com um hero no topo, depois vem a faixa de filtros e por fim a tabela. O fluxo visual é sequencial: contexto primeiro, refinamento depois, dados por último.
- **Hero superior**: O hero traz um rótulo pequeno de contexto, um título dinâmico e uma frase curta explicativa. O título muda conforme a listagem está normal ou restrita a itens novos.
- **Posição do hero**: O hero ocupa a largura da área de conteúdo e fica isolado do restante por margem inferior grande. Ele é a primeira coisa que o usuário lê depois da topbar e funciona como a âncora visual da página.
- **Bloco de filtros**: Os filtros formam um card/laje próprio, com borda, fundo e sombra iguais ao resto do shell. Ele é visualmente mais importante que a tabela porque a tela vive de consulta refinada.
- **Forma do card de filtros**: O card é largo, retangular e com cantos arredondados. Dentro dele, os campos ficam em duas linhas lógicas, e os botões de ação ficam alinhados à direita na linha principal.
- **Linha principal de filtros**: Na primeira linha ficam igreja, busca geral e ações. A igreja entra primeiro como seletor; a busca geral vem ao lado com placeholder; filtrar e limpar ficam agrupados à direita.
- **Linha avançada**: A segunda linha traz dependência, tipo de bem e status. Isso deixa claro que a tela trabalha com dois níveis de recorte: rápido e detalhado.
- **Ausência de cards de resumo**: Aqui não há card de KPI nem bloco promocional. O visual aposta no utilitário puro: filtros, tabela e paginação.
- **Tabela principal**: No desktop a tabela mostra Produto, Dependência, Status e Ações. A coluna Produto é a mais rica, com código em monoespaçada no topo da célula, descrição principal em seguida e tipo de bem em nota menor abaixo. Dependência é curta e objetiva. Status empilha cápsulas. Ações traz basicamente editar.
- **Leitura da coluna Produto**: O texto do produto é organizado em três níveis: o código, o nome legível e, quando existe, o tipo. Essa disposição permite ver a identificação principal sem perder o contexto técnico.
- **Informação por linha**: Cada linha funciona quase como um mini-resumo do bem. O código não fica escondido, a descrição não fica esmagada e o tipo aparece como contexto secundário logo abaixo.
- **Cápsulas de status**: As cápsulas dizem 14.1, nota fiscal, novo e editado. A leitura é de diagnóstico rápido, não de formulário.
- **Estado vazio**: Quando não há itens, a tabela some para um empty state simples. A tela evita parecer quebrada ou incompleta.
- **Rodapé da tabela**: A paginação fica abaixo, separada do corpo da tabela, para não disputar atenção com as linhas. Ela mostra página atual, total de páginas e links anterior/próximo.
- **Mobile**: Em telas menores, cada linha vira bloco empilhado. A tabela usa `data-label` para dar rótulo a cada célula, então a leitura deixa de ser horizontal e passa a ser vertical. Produto continua sendo o bloco principal, status vira chips curtos e ação vira botão isolado. O comportamento de mobile mantém a ordem Produto > Dependência > Status > Ações.
- **Sensação de uso**: Parece uma estação de trabalho para procurar, validar, entrar em edição e voltar para a lista com o mínimo de fricção.
