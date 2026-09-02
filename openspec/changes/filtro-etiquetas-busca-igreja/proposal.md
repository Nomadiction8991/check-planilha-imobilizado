# Proposal: Busca filtrável de igreja nos filtros de etiquetas

## Why

A tela de etiquetas (`/labels`) exibe o seletor de igreja com todas as unidades carregadas (codigo - descricao) em um único select sem filtro e com `onchange="this.form.submit()"`. Com muitas igrejas o operador precisa rolar manualmente até achar a unidade — lento no mobile e propenso a erro. O mesmo problema já foi resolvido nas telas de produtos, verificação, relatórios, acesso público e dependências com busca filtrável client-side; a experiência deve ser consistente na listagem de etiquetas.

## What Changes

- Reaproveita o padrão de busca filtrável já validado em produtos/relatórios/acesso público/dependências: campo digitável que filtra em tempo real as opções do select de igreja por correspondência case-insensitive, sem recarregar a página e sem alterar regras de listagem no servidor.
- Aplica na tela que contém o filtro de igreja: `labels.index` (etiquetas). Mantém o name `comum_id` e o submit GET existente — o filtro é apenas de apresentação. Remove o `onchange` automático do select para evitar submit antes de filtrar; o usuário confirma com o botão Filtrar.
- Trata estado sem resultados com mensagem acessível e desabilita o select até que a busca seja ajustada, igual ao comportamento já entregue nos outros filtros.

## Capabilities

### New Capabilities

- (nenhuma — amplia capacidade existente)

### Modified Capabilities

- `etiquetas-listagem`: filtros da tela de etiquetas passam a oferecer busca filtrável de igreja.

## Impact

- View `resources/views/labels/index.blade.php` (adição de input de busca + JS leve).
- Nenhuma mudança em rotas, controllers, serviços ou migrations.
- Testes de feature cobrindo presença do campo de busca na tela.
