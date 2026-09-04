## Why

Ao alterar administração ou estado na tela de relatórios, a igreja escolhida anteriormente pode deixar de pertencer às opções filtradas. Mesmo assim, o identificador antigo continua sendo usado para carregar relatórios, deixando a interface incoerente e podendo mostrar dados de uma igreja diferente do escopo exibido.

## What Changes

- Validar a igreja selecionada contra a lista já filtrada por administração e estado.
- Limpar a seleção de igreja quando ela não fizer parte das opções atuais.
- Evitar carregar relatórios para uma igreja incompatível com os filtros ativos.
- Preservar a seleção e os relatórios quando a igreja ainda pertencer ao escopo filtrado.
- Cobrir os cenários de seleção válida e seleção obsoleta com testes de feature.

## Capabilities

### New Capabilities

### Modified Capabilities

- `relatorios-listagem`: a seleção de igreja deve permanecer compatível com administração e estado ativos antes de carregar relatórios.

## Impact

A mudança afeta o controlador e os testes da listagem de relatórios, sem alterar rotas, contratos de serviço, banco de dados ou formato dos relatórios. O ajuste atua no mesmo conjunto de igrejas já filtrado pelo serviço existente.
