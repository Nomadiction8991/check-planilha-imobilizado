## Why

A tela de relatórios exige selecionar filtros e enviar o formulário antes de atualizar a lista de relatórios. Durante a conferência, esse passo extra torna a troca de administração, estado ou igreja especialmente lenta no celular.

## What Changes

- Atualizar automaticamente a consulta de relatórios quando administração, estado ou igreja forem alterados.
- Manter o botão "Carregar relatórios" para envio explícito e compatibilidade.
- Preservar os critérios selecionados ao atualizar um filtro e evitar submissões duplicadas.
- Exibir feedback acessível durante a navegação automática.
- Não submeter automaticamente os campos de busca locais de administração e igreja.

## Capabilities

### New Capabilities

Nenhuma.

### Modified Capabilities

- `relatorios-listagem`: a listagem passa a atualizar automaticamente após mudanças nos filtros, mantendo a submissão manual disponível.

## Impact

A mudança afeta a view server-rendered da listagem de relatórios e o JavaScript de interação no navegador. O backend continua recebendo a mesma requisição GET e os mesmos parâmetros, sem alteração de autorização, consulta ou formato de resposta.
