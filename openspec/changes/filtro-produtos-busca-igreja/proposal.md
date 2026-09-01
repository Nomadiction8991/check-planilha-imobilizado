# Proposal: Busca filtrável de igreja nos filtros de produtos

## Why

As telas de listagem e verificação de produtos já exibem o seletor de igreja com todas as unidades carregadas (codigo - descricao) em um único select sem filtro. Com muitas igrejas o operador precisa rolar manualmente até achar a unidade — lento no mobile e propenso a erro. O mesmo problema já foi resolvido na tela de acesso público (/assinatura-publica) com busca filtrável client-side; a experiência deve ser consistente nos filtros internos de produtos.

## What Changes

- Reaproveita o padrão de busca filtrável já validado no acesso público: campo digitável que filtra em tempo real as opções do select de igreja por correspondência case-insensitive, sem recarregar a página e sem alterar regras de listagem/paginação no servidor.
- Aplica nas duas telas internas que contêm o filtro de igreja: `products.index` (listagem) e `products.verification` (verificação). Mantém o name `comum_id` e o submit GET existente — o filtro é apenas de apresentação.
- Trata estado sem resultados com mensagem acessível e desabilita o select até que a busca seja ajustada, igual ao comportamento já entregue em public-access.

## Capabilities

### New Capabilities

- (nenhuma — amplia capacidade existente)

### Modified Capabilities

- `produtos-listagem`: filtros da listagem/verificação passam a oferecer busca filtrável de igreja.

## Impact

- Views `resources/views/products/index.blade.php` e `resources/views/products/verification.blade.php` (adição de input de busca + JS leve por tela).
- Nenhuma mudança em rotas, controllers, serviços ou migrations.
- Testes de feature cobrindo presença do campo de busca nas duas telas.
