## Why

A tela de etiquetas exige que o usuário escolha um filtro e só então clique em “Filtrar”, mesmo quando a seleção de igreja, administração ou dependência já mudou. Isso cria uma etapa extra no fluxo principal de impressão, especialmente incômoda no celular, e pode deixar os códigos exibidos divergentes do filtro que acabou de ser escolhido.

## What Changes

- Submeter automaticamente o formulário de filtros quando administração, estado, igreja ou dependência forem alterados.
- Aplicar debounce à busca geral de administração e igreja sem disparar navegações enquanto o usuário apenas procura uma opção no seletor.
- Manter o envio manual, limpar filtros e feedback acessível de atualização.
- Reiniciar a paginação implicitamente ao aplicar um novo conjunto de filtros.

## Capabilities

### New Capabilities

### Modified Capabilities

- `etiquetas-listagem`: a tela passa a atualizar os resultados automaticamente após alterações nos filtros enviados ao servidor.

## Impact

A mudança afeta somente a interface da tela de etiquetas e sua navegação GET existente. Não altera a API de persistência de etiquetas manuais, as regras de autorização ou a consulta dos produtos; o botão de envio continua disponível como alternativa e nenhuma dependência nova será adicionada.
