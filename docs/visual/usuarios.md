# Visual: Gestão de Usuários

## Descrição Estética:
- **Layout**: A tela usa hero, filtros, tabela e ações por linha. Não há cards grandes; o foco é uma listagem limpa com manutenção e controle de acesso.
- **Hero superior**: O texto informa que os usuários são vinculados a administrações, não a igrejas. Esse detalhe define a lógica do módulo e aparece logo no topo.
- **Filtro**: Há um seletor de administração, uma busca por nome ou email e um filtro avançado de status. Isso cria uma leitura direta e operacional.
- **Tabela**: A tabela mostra nome e email do usuário, administração vinculada, status e ações. A primeira coluna é mais rica visualmente porque empilha nome e subtítulo; a segunda mantém o vínculo organizacional; a terceira traz cápsula de status.
- **Status visual**: Ativo e inativo aparecem como cápsulas com cores opostas. O status é rápido de escanear e ajuda a separar contas vivas de contas desativadas.
- **Ações**: Permissões, editar e excluir ficam agrupadas à direita. Em contas protegidas, alguns botões somem, o que simplifica a linha visualmente.
- **Estados vazios e paginação**: Se a lista estiver vazia, entra o empty state. Se houver muitos usuários, a paginação segue abaixo da tabela.
- **Mobile**: As linhas quebram em blocos empilhados com `data-label`. Nome e email aparecem juntos, administração vem logo depois e os botões de ação podem se agrupar verticalmente conforme o espaço.
