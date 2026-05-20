# Visual: Logs de Auditoria

## Descrição Estética:
- **Layout**: A tela usa filtros no topo e tabela densa no corpo. Não há hero grandão nem cards de KPI; o objetivo é inspeção e rastreabilidade.
- **Filtros**: A faixa superior traz busca geral, módulo e intervalo de datas. Isso já diz que a tela é de apuração histórica e não de ação imediata.
- **Tabela**: As colunas mostram data, usuário, módulo, ação, descrição e origem. A tabela é o elemento principal e carrega a maior parte da informação.
- **Coluna de data**: A data vem em fonte monoespaçada e com horário completo. Ela serve como eixo temporal da leitura.
- **Coluna de usuário**: O nome do usuário aparece com email abaixo, e ainda pode mostrar administração ou igreja como subtítulo. Isso cria uma mini-identidade dentro da linha.
- **Coluna de módulo**: O módulo aparece como cápsula escura, chamando atenção para o contexto do evento.
- **Coluna de descrição**: A descrição traz o que aconteceu e, se existir, a rota aparece em linha menor abaixo.
- **Coluna de origem**: Método e status aparecem em cápsulas, e abaixo vêm path e IP. Essa coluna fecha a linha como “prova” de origem.
- **Responsividade**: No mobile, a tabela vira blocos empilhados por `data-label`. Mesmo sem largura grande, os campos continuam legíveis e a leitura segue a ordem temporal.
