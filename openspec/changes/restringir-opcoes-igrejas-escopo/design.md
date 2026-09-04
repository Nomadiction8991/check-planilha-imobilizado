## Context

A navegação de igrejas já centraliza paginação, busca textual e filtros de administração e estado. O serviço, porém, consulta as tabelas sem aplicar a mesma noção de escopo administrativo usada pela navegação de produtos, deixando usuários restritos verem igrejas externas.

## Goals / Non-Goals

**Goals:**

- Reutilizar a definição de escopo administrativo da sessão para a consulta de igrejas.
- Incluir a administração principal e as administrações adicionais permitidas.
- Aplicar o escopo tanto na listagem paginada quanto nas opções de administração.
- Preservar acesso global de administradores e todos os filtros atuais.

**Non-Goals:**

- Alterar o schema, rotas, nomes de parâmetros ou políticas de edição.
- Alterar a consulta pública de seleção de igreja.
- Introduzir consultas remotas ou mudanças visuais na tela.

## Decisions

1. **Resolver o escopo na camada de navegação.** A consulta de igrejas usará a sessão como fonte de autorização antes dos filtros enviados pelo navegador. Isso evita depender de um `administracao_id` manipulável na URL. A alternativa de validar somente no controller foi rejeitada porque deixaria outros consumidores do serviço sem a mesma proteção.

2. **Diferenciar sessão global de sessão restrita.** A ausência de marcadores de autenticação continua compatível com testes e fluxos legados que consultam globalmente; quando a sessão declara usuário não administrador, a lista de IDs será formada pela administração principal e pelas adicionais permitidas. Sem IDs em uma sessão restrita, a consulta não retornará igrejas.

3. **Aplicar o filtro solicitado dentro do escopo.** A consulta combinará o escopo obrigatório com o filtro de administração escolhido. Assim, uma administração externa não pode ampliar o resultado, mesmo que seja enviada diretamente na URL.

4. **Manter opções de filtro coerentes com a listagem.** As opções de administração serão limitadas ao mesmo conjunto autorizado, evitando que o select ofereça critérios que nunca podem produzir resultados.

## Risks / Trade-offs

- **[Risco]** Sessões antigas sem dados de autenticação podem ser interpretadas como globais. **Mitigação:** preservar o comportamento legado somente quando não houver marcador de sessão restrita, como no restante do navegador de produtos.
- **[Risco]** Dados legados podem possuir igrejas sem administração. **Mitigação:** usuários restritos receberão somente vínculos com IDs autorizados; administradores globais continuam vendo registros sem vínculo.
- **[Risco]** A tela administrativa pode ficar vazia para uma sessão restrita incompleta. **Mitigação:** esse é o comportamento seguro; a sessão deve ser reestabelecida pelo login, sem abrir dados por fallback global.

## Migration Plan

Nenhuma migração ou dependência nova. Após a implementação, executar os testes do navegador de igrejas, a suíte PHPUnit e as sondas de saúde. O rollback consiste em reverter o commit, sem alteração de dados persistidos.