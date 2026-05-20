# Visual: Gestão Administrativa

## Descrição Estética:
- **Layout**: A tela usa hero, filtros e tabela. O hero abre o contexto, a faixa de filtros fica logo abaixo e a tabela ocupa o corpo principal. Não há cards grandes, porque o foco é administração crua.
- **Hero superior**: O título informa que são as administrações do sistema e o subtítulo explica que esse cadastro é base para importações, igrejas e localização. É uma entrada que posiciona a tela como cadastro estrutural.
- **Filtro**: Existe uma busca única por ID, descrição ou CNPJ. O filtro fica em uma linha simples para não criar ruído visual num módulo que já é técnico.
- **Botão de criação**: O botão “Nova administração” fica na faixa do cabeçalho da seção, à direita, como ação primária do módulo.
- **Tabela**: A tabela mostra ID, descrição, CNPJ, localização e ações. A composição é clássica: dado técnico à esquerda, descrição central e ações à direita.
- **Ações por linha**: Editar e excluir aparecem no canto direito, agrupadas. Se não houver permissão, a linha fica mais limpa.
- **Estados vazios e paginação**: Quando não há resultado, entra o empty state. Quando há muitos itens, a paginação aparece logo abaixo da tabela.
- **Mobile**: As linhas viram blocos empilhados com labels próprios em `data-label`. O ID e o CNPJ usam tipografia monoespaçada, o que ajuda a distinguir dado técnico do texto descritivo.
