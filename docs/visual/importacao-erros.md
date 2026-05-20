# Visual: Erros de Importação

## Descrição Estética:
- **Layout**: Tabela destacando motivos de erro em vermelho e botões de download de CSV. Essa tela é o pós-fracasso da importação, então ela precisa ser extremamente clara sobre o que deu errado e onde a correção deve começar.
- **Bloco de alerta**: Normalmente há um aviso de resumo indicando quantos registros falharam e qual o contexto da importação. Esse bloco funciona como entrada para a triagem.
- **Tabela de falhas**: Cada linha carrega linha de origem, motivo, status e eventual ação de resolução. A leitura precisa ser rápida porque o usuário quase sempre vem aqui para encontrar um problema específico.
- **Ações auxiliares**: O download em CSV costuma ficar próximo da tabela, permitindo exportar o erro para análise externa. Isso ajuda quando a correção acontece fora da interface.
- **Cores**: A tela usa vermelho como sinal dominante de erro, mas sem depender só da cor para entendimento. O texto e a estrutura também precisam deixar claro o problema.
- **Responsividade**: Elementos se adaptam para visualização em tablets e desktops. Mesmo em telas menores, a identificação do erro precisa continuar fácil de localizar.
