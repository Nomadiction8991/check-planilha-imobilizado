## Why

As listas de administrações, igrejas, dependências, tipos de bem e usuários ainda exigem tocar em “Filtrar” para cada alteração, o fluxo de consulta fica mais lento e inconsistente com as telas que já atualizam os resultados automaticamente. A melhoria reduz passos repetitivos sem alterar os parâmetros GET nem retirar o envio manual.

## What Changes

- Submeter automaticamente a consulta quando um filtro enviado ao servidor for alterado nas cinco listagens de cadastro.
- Aplicar debounce à busca textual e enviar imediatamente quando o campo de busca for limpo pelo controle nativo do navegador.
- Evitar navegações duplicadas quando a assinatura dos valores não mudou.
- Exibir feedback textual acessível durante a atualização e preservar o botão de filtragem para o envio manual.
- Manter as buscas auxiliares de administração e igreja como filtros locais, sem enviá-las ao servidor.

## Capabilities

### New Capabilities

<!-- Nenhuma capacidade nova; a melhoria estende capacidades de listagem existentes. -->

### Modified Capabilities

- `administrations`: atualização automática dos filtros da listagem de administrações.
- `churches`: atualização automática dos filtros da listagem de igrejas.
- `departments`: atualização automática dos filtros da listagem de dependências.
- `asset-types`: atualização automática dos filtros da listagem de tipos de bem.
- `users`: atualização automática dos filtros da listagem de usuários.

## Impact

As views das cinco listagens receberão atributos declarativos para identificar o formulário e seus campos server-side, e o layout compartilhado fornecerá a rotina JavaScript de submissão automática. Os testes de apresentação verificarão os contratos necessários; não há alteração de rota, banco de dados, API ou dependência externa.
