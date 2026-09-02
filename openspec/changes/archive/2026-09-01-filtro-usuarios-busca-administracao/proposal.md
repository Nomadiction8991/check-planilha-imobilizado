# Proposal: Busca filtrável de administração na lista de usuários

## Why

A tela de usuários (`/users`) exibe o seletor de administração com todas as unidades em um único select sem filtro. Com muitas administrações o operador precisa rolar manualmente até achar a unidade — lento no mobile e propenso a erro. O mesmo problema já foi resolvido nas telas de produtos, etiquetas, dependências, relatórios e acesso público com busca filtrável client-side; a experiência deve ser consistente também na listagem de usuários.

## What Changes

- Reaproveita o padrão de busca filtrável já validado: campo digitável que filtra em tempo real as opções do select de administração por correspondência case-insensitive, sem recarregar a página e sem alterar regras de listagem no servidor.
- Aplica na tela `users.index` (`GET /users`). Mantém o name `administracao_id` e o submit GET existente — o filtro é apenas de apresentação.
- Trata estado sem resultados com mensagem acessível e desabilita o select até que a busca seja ajustada, igual ao comportamento já entregue em produtos/relatórios/etiquetas.

## Capabilities

### New Capabilities

- (nenhuma — amplia capacidade existente)

### Modified Capabilities

- `usuarios-listagem`: filtros da listagem de usuários passam a oferecer busca filtrável de administração.

## Impact

- View `resources/views/users/index.blade.php` (adição de input de busca + JS leve).
- Nenhuma mudança em rotas, controllers, serviços ou migrations.
- Testes de feature cobrindo presença do campo de busca na tela de usuários.
