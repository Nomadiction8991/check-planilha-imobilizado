## Context
Listagem e verificação de produtos já disparam submissão automática ao alterar selects e busca. Quando a paginação está em página >1, o parâmetro `page`/`pagina` segue no GET e pode apontar para página inexistente após o filtro reduzir o total.

## Goals / Non-Goals
**Goals:** garantir que toda submissão automática de filtros envie o formulário sem parâmetro de página, voltando à primeira página.
**Non-Goals:** mudar paginação do servidor, alterar escopo administrativo ou contrato de filtros.

## Decisions
- Antes de enviar automaticamente, limpar e desabilitar inputs ocultos/nome `page`/`pagina` no formulário, evitando que sejam serializados no GET. Alternativa de reescrever URL manualmente foi descartada por duplicar lógica de `FormData`.
- Reusar o mesmo helper nas duas telas (`index` e `verification`), mantendo submissão manual intacta.

## Risks / Trade-offs
- Se existir paginação fora do form (query na URL), continua sendo sobrescrita pela nova submissão sem página — desejável. Mitigação: confirmar que o parâmetro de página vive no form ou é omitido no submit.
- Duplicação do script entre as duas views até extração futura para partial — aceitável para mudança pontual.

## Migration Plan
Sem migração. Deploy via push na main. Rollback revertendo helper de reset de paginação.
