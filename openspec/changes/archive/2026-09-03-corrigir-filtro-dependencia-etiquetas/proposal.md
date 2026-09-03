## Why

A tela de etiquetas filtra os produtos marcados para impressão pela dependência atualmente selecionada, mas a consulta usa qualquer valor editado preenchido, mesmo quando o produto não está marcado como editado. Assim, registros legados com um valor residual podem aparecer na dependência errada ou desaparecer da dependência original, dificultando a conferência patrimonial.

## What Changes

- Aplicar a mesma regra de classificação atual usada na listagem de produtos ao filtro de dependência da tela de etiquetas.
- Considerar a dependência editada somente quando o produto estiver marcado como editado e a relação editada tiver descrição exibível.
- Usar a dependência original quando o produto não estiver editado, quando o vínculo editado não existir ou quando não tiver valor exibível.
- Montar a lista de dependências e a lista de produtos com o mesmo critério, evitando opções que não retornam resultados.
- Cobrir produtos editados, não editados e com vínculo editado inválido com testes de serviço.

## Capabilities

### New Capabilities

### Modified Capabilities

- `etiquetas-listagem`: o filtro de dependência e as opções da tela devem refletir a dependência atual do produto, com fallback seguro para o vínculo original.

## Impact

A mudança afeta a consulta de dados da cópia de etiquetas e seus testes. Não altera rotas, permissões, persistência das etiquetas manuais ou o banco de dados. Produtos marcados para impressão continuam sendo os únicos considerados.
