# Proposal: Busca filtrável de igreja na lista de relatórios

## Why

A tela de relatórios (`/reports`) exibe o seletor de igreja com todas as unidades carregadas (codigo - descricao) em um único select sem filtro. Com muitas igrejas o operador precisa rolar manualmente até achar a unidade — lento no mobile e propenso a erro. O mesmo problema já foi resolvido nas telas de produtos e acesso público com busca filtrável client-side; a experiência deve ser consistente na listagem de relatórios, que é o ponto de entrada de todos os formulários 14.x.

## What Changes

- Reaproveita o padrão de busca filtrável já validado em produtos/acesso público: campo digitável que filtra em tempo real as opções do select de igreja por correspondência case-insensitive, sem recarregar a página e sem alterar regras de listagem no servidor.
- Aplica na tela `reports.index` (`GET /reports`). Mantém o name `comum_id` e o submit GET existente — o filtro é apenas de apresentação.
- Trata estado sem resultados com mensagem acessível e desabilita o select até que a busca seja ajustada, igual ao comportamento já entregue em produtos.

## Capabilities

### New Capabilities

- (nenhuma — amplia capacidade existente)

### Modified Capabilities

- `relatorios-formulario`: filtros da listagem de relatórios passam a oferecer busca filtrável de igreja.

## Impact

- View `resources/views/reports/index.blade.php` (adição de input de busca + JS leve).
- Nenhuma mudança em rotas, controllers, serviços ou migrations.
- Testes de feature cobrindo presença do campo de busca na tela de relatórios.
