# Visual: Cadastro de Produto

## Descrição Estética:
- **Composição geral**: A tela começa com o hero de contexto, depois entra em um card grande com o formulário e termina com as ações de salvar e cancelar. A leitura é vertical e bem guiada, sem blocos soltos fora da sequência.
- **Posição do hero**: O hero fica no topo da área de conteúdo e abre o tema “Cadastro de produtos”. Ele usa um título curto e uma frase explicativa que já orienta sobre as regras da tela antes do formulário começar.
- **Seção superior do formulário**: Logo na entrada aparecem campos básicos de identificação, como igreja, código, multiplicador, tipo de bem, bem e dependência. Essa primeira parte é a mais importante porque define o vínculo estrutural do registro.
- **Distribuição dos campos**: Os campos iniciais aparecem em grade de múltiplas colunas para reduzir altura e aproveitar a largura da tela. Igreja, código e multiplicador costumam ser lidos primeiro, porque são a identificação mínima do item.
- **Blocos de informação**: Depois vêm o campo de complemento em largura maior e, abaixo, a linha de dimensões físicas. A tela separa bem o que é identificação, o que é descrição e o que é medida.
- **Complemento**: O complemento ocupa a largura da linha e serve como campo de texto livre, portanto ele visualmente pede mais espaço que os campos curtos ao redor.
- **Área fiscal**: A condição 14.1 aparece como seletor próprio e, conforme a escolha, libera ou esconde os campos de nota fiscal. Isso faz a tela responder ao contexto do cadastro em vez de mostrar tudo sempre.
- **Campos de nota**: Quando ativos, os campos de número, data, valor e fornecedor entram em um bloco separado. Visualmente isso deixa claro que são dados condicionais e não obrigatórios em todos os casos.
- **Rodapé de ações**: Os botões de salvar e cancelar ficam juntos no fim, com o salvar como ação principal. O usuário só chega neles depois de passar por todo o formulário.
- **Posição dos botões**: Os botões ficam alinhados na parte inferior do card, depois de todos os campos, reforçando o padrão de fluxo vertical. O botão primário chama atenção primeiro e o cancelamento fica ao lado como saída secundária.
- **Densidade**: É uma tela extensa e técnica. O visual é de formulário pesado, mas organizado por blocos, para não parecer uma parede de inputs.
- **Responsividade**: Em desktop, os campos aparecem em grades de três colunas em vários trechos. No mobile, a grade empilha e cada bloco vira uma sequência vertical, mantendo a ordem lógica dos dados. O seletor de igreja, o tipo de bem e a dependência continuam aparecendo antes do campo de complemento e da área fiscal.
