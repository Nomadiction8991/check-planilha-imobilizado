# Visual: Progresso de Importação

## Descrição Estética:
- **Layout**: A página funciona como uma tela de monitoramento. No topo existe um hero de contexto, e no corpo fica a área de progresso com status do processamento. A composição é feita para ser lida enquanto o sistema trabalha.
- **Hero superior**: O título informa que a importação está em processamento. Logo abaixo há uma frase curta pedindo paciência. Esse hero abre a tela como estado transitório, não como página de ação.
- **Painel principal**: O bloco central mostra o nome do arquivo ou a identificação da importação e, abaixo, a barra de progresso. O bloco visual tem peso maior que o restante porque ele representa o estado da operação.
- **Barra de progresso**: A barra é animada e aparece como indicador central. Ela traduz em imagem o que está acontecendo no backend, então o usuário sabe se o lote está andando ou não.
- **Contadores e mensagens**: A tela mostra contadores e mensagens de etapa para explicar em que fase a importação está. Isso evita leitura de logs ou dependência de outra aba.
- **Hierarquia**: O título e a barra ficam no foco, enquanto textos auxiliares ficam abaixo ou ao lado. A tela não quer ser bonita, quer ser clara e confiável.
- **Cores**: O sistema usa cores do tema para manter contraste. O progresso não depende de enfeite; ele depende de leitura rápida.
- **Responsividade**: Em telas menores a página continua legível, com o bloco principal empilhado e a barra sempre visível. A prioridade é o acompanhamento, não a densidade visual.
