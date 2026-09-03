## Context

Consulte `proposal.md` para a motivação. O DTO de filtros já converte parâmetros da requisição em um estado tipado, enquanto as duas telas de produtos calculam o contador diretamente da query e o componente de chips monta links removendo uma chave por vez. A compatibilidade com URLs legadas exige aceitar as duas chaves, mas a apresentação deve tratar o significado, não a quantidade de aliases.

## Goals / Non-Goals

**Goals:**

- Ter uma única representação semântica de “somente novos” no contador e nos chips.
- Preservar URLs legadas na entrada e produzir links de paginação canônicos.
- Fazer a remoção do filtro retirar todos os aliases equivalentes.
- Verificar o comportamento no DTO e na resposta HTML das telas.

**Non-Goals:**

- Alterar a consulta SQL ou os registros retornados.
- Remover suporte aos parâmetros legados.
- Redesenhar o restante dos filtros ou mudar a regra de colapsamento mobile.

## Decisions

- **Contar a partir do estado tipado:** adicionar ao DTO uma operação de contagem semântica e usar esse resultado nas duas views, em vez de contar chaves cruas da query. Assim aliases equivalentes contam uma vez e a regra fica centralizada.
- **Serializar `status=novos` como forma canônica:** quando o estado de itens novos também possui status “novos”, a serialização manterá o status e omitirá `somente_novos`. Para outras combinações, `somente_novos` continua sendo preservado.
- **Remover aliases em conjunto:** o callback de remoção dos chips tratará `status` e `somente_novos` como aliases apenas quando o critério for “Somente novos”. Outros status continuam removendo somente seu próprio parâmetro.
- **Alternativa descartada:** corrigir apenas o texto das views manteria a paginação duplicada e permitiria que a remoção reativasse o filtro. A normalização no DTO e no componente cobre entrada, navegação e saída.

## Risks / Trade-offs

- [Risco] Links salvos com as duas chaves podem perder uma chave ao navegar. → [Mitigação] As duas chaves têm o mesmo significado nesse caso e a query resultante mantém `status=novos`, que já ativa a regra existente.
- [Risco] Um usuário pode combinar “somente novos” com outro status válido. → [Mitigação] A contagem semântica considera o status adicional separadamente quando ele não é `novos`, preservando a informação da consulta.

## Migration Plan

Nenhuma migração de dados é necessária. A alteração entra junto com o código da aplicação; rollback consiste em reverter o commit caso a normalização apresente incompatibilidade inesperada.