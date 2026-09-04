## Context

Consulte `proposal.md` para a motivação. O navegador de produtos já calcula uma lista comum de administrações permitidas para filtrar produtos, igrejas, dependências e administrações. A consulta de tipos de bem usa uma regra separada que considera somente a administração ativa, embora a mesma opção seja reutilizada nos filtros e nos formulários de criação e edição.

## Goals / Non-Goals

**Goals:**

- Reutilizar a definição existente de escopo administrativo ao carregar tipos de bem para produtos.
- Permitir tipos vinculados à administração ativa ou a qualquer administração adicional autorizada.
- Preservar tipos compartilhados sem administração e o acesso global de administradores.
- Manter o comportamento legado quando o esquema não tiver a coluna de administração.

**Non-Goals:**

- Alterar o esquema, migrar tipos existentes ou mudar a política de escopo dos módulos de cadastro de tipos de bem.
- Alterar a consulta de produtos, rotas, filtros enviados ou permissões.
- Transformar o seletor em uma busca remota ou modificar a apresentação visual.

## Decisions

1. **Usar a lista de escopo já calculada pelo navegador de produtos.** A consulta de opções deve chamar a mesma resolução que protege a paginação. Isso evita que `administracoes_permitidas` seja interpretado de forma diferente em telas relacionadas. Alternativa rejeitada: duplicar apenas `Session::get('administracao_id')`, que foi a origem da inconsistência.

2. **Manter tipos compartilhados como exceção explícita.** Quando a coluna existe, a consulta combinará `whereIn` para administrações permitidas com `orWhereNull` para tipos globais. Uma sessão explicitamente restrita sem IDs continuará sem tipos vinculados, mas tipos compartilhados permanecerão tratados segundo a regra de opções autorizadas definida pelo serviço.

3. **Preservar fallback de esquema.** Se `tipos_bens.administracao_id` não existir, nenhuma condição administrativa será aplicada, mantendo compatibilidade com a estrutura legada já suportada pelo serviço.

## Risks / Trade-offs

- **[Risco]** Uma sessão antiga pode não carregar nenhuma informação de escopo. **Mitigação:** preservar o retorno global do resolvedor quando a sessão não sinaliza restrição, como já ocorre na listagem de produtos.
- **[Risco]** Tipos compartilhados podem ser usados por produtos de qualquer administração. **Mitigação:** mantê-los disponíveis explicitamente, pois não pertencem a uma administração específica.
- **[Risco]** A consulta pode expor opções permitidas que não possuem produtos na igreja selecionada. **Mitigação:** o seletor já representa o catálogo permitido para o formulário; dependências continuam condicionadas à igreja e a consulta de produtos valida o resultado no servidor.

## Migration Plan

Nenhuma migração ou dependência nova. Após a implementação, executar os testes unitários do navegador de produtos, a suíte PHPUnit e as sondas de saúde. O rollback consiste em reverter o commit, sem alteração de dados persistidos.
