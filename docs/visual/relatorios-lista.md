# Visual: Central de Relatórios

## Descrição Estética:
- **Layout real da tela**: Apesar do nome “central”, a tela não usa grid de cards no código atual; ela usa hero no topo e uma tabela logo abaixo. Isso deixa a página mais técnica e mais próxima de um índice operacional do que de uma vitrine visual.
- **Hero superior**: O título fala que relatórios 14.x e posição de estoque já navegam no novo app. Logo abaixo existe uma frase curta explicando que a igreja precisa ser selecionada para listar os formulários e backups.
- **Filtro principal**: A página começa com um seletor de igreja e dois botões, carregar e limpar. Esse bloco fica sozinho antes da tabela e determina se a lista aparece ou não.
- **Tabela da lista**: Quando a igreja é selecionada, a tela mostra uma tabela com colunas de código, descrição, título, itens e ação. Esse formato funciona como catálogo técnico de relatórios disponíveis.
- **Conteúdo de cada linha**: O código identifica o formulário, a descrição mostra o nome funcional, o título explica o que será renderizado, a coluna de itens informa o volume e a ação abre a saída correta.
- **Botões e posição**: O botão principal fica na última coluna e muda de rótulo conforme a linha seja posição de estoque ou prévia normal. Isso deixa o destino explícito antes do clique.
- **Estado vazio**: Antes da seleção da igreja, a tela fica vazia com uma orientação. Se não houver relatório para aquela igreja, aparece outro empty state.
- **Responsividade**: No mobile, a tabela vira leitura vertical com labels por linha. O conteúdo continua inteiro, só reorganizado em blocos empilhados.
