# Proposal: Acesso público com busca de igreja

## Why

A tela de acesso público lista todas as igrejas em um único select sem filtro. Com dezenas a centenas de unidades, encontrar a igreja certa é lento e propenso a erro, especialmente em dispositivos móveis.

## What Changes

- Adiciona campo de busca digitável na tela de acesso público que filtra as opções de igreja em tempo real, sem recarregar a página.
- Mantém o envio do formulário e a validação existentes; o filtro é apenas de apresentação e não altera regras de autenticação ou sessão.
- Garante que quando nenhuma igreja corresponde à busca, uma mensagem amigável seja exibida e o select seja desabilitado de forma acessível.

## Capabilities

### New Capabilities

- `public-access`: fluxo de acesso público para seleção de igreja com suporte à busca filtrável.

### Modified Capabilities

- (nenhuma — nova capacidade)

## Impact

- View `resources/views/public-access/create.blade.php` (adição de input de busca + JS leve).
- Nenhuma mudança em rotas, controllers ou migrations.
- Testes de feature cobrindo presença do campo de busca e comportamento de filtro via marcação.
