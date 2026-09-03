## Why

A listagem de produtos exige abrir a seção de filtros, preencher os critérios e enviar o formulário para só então ver o resultado. Esse fluxo é repetitivo no uso diário, especialmente no celular, onde o usuário alterna entre filtros para localizar bens durante a conferência.

## What Changes

- Aplicar filtros de produtos automaticamente quando o usuário altera selects ou limpa a busca geral.
- Manter o botão "Filtrar" para submissão explícita, compatibilidade e acessibilidade.
- Preservar a consulta atual e os demais critérios ao atualizar um único filtro.
- Evitar submissões automáticas durante a digitação da busca geral; atualizar após uma pausa curta ou ao confirmar a busca.
- Exibir estado de processamento para que a atualização pareça intencional e não gere múltiplas requisições concorrentes.

## Capabilities

### New Capabilities

- Nenhuma.

### Modified Capabilities

- `produtos-listagem`: as telas de listagem e verificação passam a atualizar os resultados automaticamente após mudanças nos filtros, mantendo a submissão manual disponível.

## Impact

A mudança afeta os formulários de filtros nas views de produtos e o JavaScript executado no navegador. Não altera contratos HTTP, regras de autorização, paginação, escopo administrativo ou a consulta de produtos no servidor; a atualização continua sendo uma requisição GET com os mesmos parâmetros existentes.
