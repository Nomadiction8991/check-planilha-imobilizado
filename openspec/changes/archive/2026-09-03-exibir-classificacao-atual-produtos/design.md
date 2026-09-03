## Context

A paginação atual carrega `tipoBem` e `dependencia`, enquanto os campos legados de edição guardam IDs separados. As duas views já usam um suporte comum para montar o nome atual, mas repetem a apresentação dos vínculos originais. Ver `proposal.md` para a motivação e `specs/produtos-listagem/spec.md` para o contrato observável.

## Goals / Non-Goals

**Goals:**

- Tornar a classificação mostrada coerente com o nome atual do produto.
- Manter uma única regra de seleção para listagem e verificação.
- Eager-load os vínculos editados sem alterar o escopo, filtros ou paginação.
- Preservar fallback para dados legados incompletos.

**Non-Goals:**

- Alterar os IDs ou valores persistidos no banco.
- Mudar o formulário de edição, filtros ou fluxo de etiquetas.
- Corrigir classificações inválidas já existentes por migração automática.

## Decisions

### Relações explícitas no modelo legado

Adicionar relações `belongsTo` para tipo e dependência editados no modelo de produto, apontando para `editado_tipo_bem_id` e `editado_dependencia_id`. A alternativa de fazer joins manuais nas views foi rejeitada porque espalharia SQL e dificultaria a reutilização entre as duas telas.

### Suporte compartilhado para a classificação atual

Criar um suporte pequeno que escolha a relação editada somente quando `editado` estiver ativo e houver relação carregada/válida; caso contrário, retornar a relação original. A alternativa de duplicar condicionais em cada Blade foi rejeitada para evitar divergência entre listagem e verificação.

### Eager loading na consulta paginada

A consulta carregará as quatro relações necessárias (`tipoBem`, `dependencia` e suas versões editadas). A alternativa de lazy loading foi rejeitada por gerar consultas N+1 em páginas com muitos produtos.

## Risks / Trade-offs

- **Relação editada com ID inválido:** a classificação editada pode não ser resolvida; o suporte mitiga isso usando o vínculo original correspondente.
- **Registros legados com `editado` inconsistente:** a regra respeita o indicador persistido e mantém o comportamento original quando não há relação editada válida.
- **Custo de eager loading:** duas relações adicionais aumentam a consulta, mas evitam uma consulta por linha e são limitadas aos IDs necessários.

## Migration Plan

Nenhuma migração é necessária. Após os testes, o deploy normal por push aplica apenas código; em caso de rollback, reverter o commit restaura a apresentação anterior sem alterar dados.
