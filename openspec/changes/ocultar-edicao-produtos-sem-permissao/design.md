## Context

As duas telas já recebem as permissões efetivas no layout compartilhado e usam o mesmo middleware para proteger as rotas. A listagem principal ainda renderiza a ação de edição sem verificar essa capacidade; a verificação também sempre monta o link de edição em cada linha.

## Goals / Non-Goals

**Goals:**

- Derivar uma única flag de apresentação para indicar se o usuário atual pode editar produtos.
- Usar essa flag nas duas views para controlar somente a renderização dos links de edição.
- Cobrir administrador, usuário autorizado e usuário apenas com acesso de consulta.

**Non-Goals:**

- Alterar middleware, permissões persistidas, rotas, consultas, checklist ou comportamento de salvamento.
- Remover a tela de verificação ou esconder os dados de produtos de usuários que só podem consultar.

## Decisions

- Calcular a capacidade na própria view a partir de `legacySessionUser.is_admin`, `session('is_admin')` e `legacyPermissions['products.edit']`, mantendo a mesma convenção já usada para ações de cabeçalho. Isso evita duplicar regra de autorização no controller e mantém a mudança restrita à apresentação.
- Envolver o link inteiro de edição em uma condição, em vez de renderizar um link desabilitado. Um controle desabilitado ainda ocupa espaço e não comunica tão bem o modo consulta, sobretudo no layout responsivo.
- Na verificação, manter a célula de ações e o restante do checklist mesmo sem edição; apenas o link de edição desaparece. Assim, o usuário continua podendo executar as ações permitidas pela tela.
- Testar o HTML renderizado nas duas telas com uma sessão sem `products.edit` e com a flag de administrador, além dos testes existentes de autorização direta.

## Risks / Trade-offs

- [Risk] A view pode receber uma permissão ausente em testes ou sessões antigas. → Mitigação: usar fallback vazio e tratar administrador explicitamente.
- [Risk] Usuários podem interpretar a ausência de botão como erro. → Mitigação: manter a identificação e o estado de consulta visíveis; o contexto da tabela continua indicando os dados disponíveis.
- [Risk] A UI não é uma barreira de segurança. → Mitigação: preservar o middleware de permissão nas rotas e validar o acesso direto nos testes existentes.

## Migration Plan

Nenhuma migração. Publicar a alteração junto com os testes; rollback consiste em restaurar as condições anteriores nas duas views, sem impacto em dados.
