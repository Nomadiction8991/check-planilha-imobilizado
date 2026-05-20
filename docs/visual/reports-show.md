# Visual: Prévia de Relatório

## Descrição Estética:
- **Layout geral**: A tela é dividida em duas partes grandes: o hero superior com resumo técnico e, abaixo, a área de documento renderizado. O fluxo visual é de leitura antes da impressão.
- **Hero superior**: À esquerda ficam o título, a explicação e os botões de voltar, editar células e imprimir. À direita fica um painel-resumo com formulário, total de páginas, chips e um toggle técnico de bordas e nomes de campos.
- **Painel de resumo**: Esse card lateral funciona como caixa de inspeção. Ele informa se o fundo foi carregado, quantas páginas existem e qual é o formulário, sem exigir que o usuário desça até o documento.
- **Chips**: Os chips do resumo e do contexto são pequenos, arredondados e compactos. Eles servem como marcadores de estado e ajudam a ver rapidamente se a visualização está completa.
- **Toggle técnico**: O controle de mostrar bordas e nomes aparece no painel direito e muda a leitura do documento para inspeção de layout. Ele é mais uma ferramenta técnica do que uma ação de usuário comum.
- **Contexto da igreja**: A faixa abaixo do hero mostra a igreja selecionada e a cidade, seguida de chips com formulário e páginas. Esse bloco confirma o contexto antes da visão do documento.
- **Área A4**: O conteúdo principal é um preview centralizado em uma folha com tamanho fixo, dentro de uma moldura com scroll. Isso faz a tela parecer uma pré-impressão real, e não uma simples página web.
- **Responsividade**: Em telas menores, o hero se reorganiza em coluna e a folha continua navegável por scroll. O documento não tenta caber inteiro à força; ele preserva a fidelidade de impressão.
