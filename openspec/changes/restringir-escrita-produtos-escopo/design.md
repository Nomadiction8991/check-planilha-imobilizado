## Context

A consulta de produtos já calcula as administrações permitidas na sessão, mas a criação e a edição delegam a resolução de IDs diretamente aos serviços de persistência. As operações rápidas também atualizam por par produto/igreja sem consultar a administração da igreja. O escopo deve ser aplicado no backend, antes de qualquer alteração, preservando a compatibilidade com o esquema legado que não possui uma coluna própria de administração em produtos.

## Goals / Non-Goals

**Goals:**

- Centralizar a resolução do escopo administrativo da sessão para reutilização pelos serviços de produtos.
- Impedir criação, edição e operações rápidas sobre igrejas fora do escopo.
- Manter a regra de administrador global e os tipos de bem compartilhados.
- Retornar falhas controladas e testáveis sem mutar registros.

**Non-Goals:**

- Alterar tabelas, migrations ou o modelo de permissões.
- Criar exclusão de produtos, que não faz parte do fluxo existente.
- Alterar a experiência ou os filtros visuais além das mensagens de erro necessárias.

## Decisions

### Uma regra de escopo baseada na administração da igreja

A autorização será determinada pela administração relacionada à igreja, não por IDs enviados pelo formulário. Usuários administradores bypassam a verificação; usuários restritos precisam ter a administração da igreja entre a administração ativa ou as administrações permitidas da sessão. Isso reutiliza a fonte que já protege a listagem e evita duplicar um campo de escopo em produtos.

Alternativa considerada: confiar no middleware de permissão de produto. Rejeitada porque a permissão diz o que o perfil pode fazer, mas não em quais administrações pode fazer.

### Validação antes da resolução de dependência e do preenchimento

A criação validará a igreja dentro do escopo antes de resolver tipo e dependência. A edição validará o produto e sua igreja antes de resolver os novos vínculos. Assim, IDs de relações manipulados não conseguem contornar o limite e nenhuma alteração parcial ocorre.

Alternativa considerada: validar apenas no FormRequest. Rejeitada porque operações rápidas recebem requisições genéricas e a regra precisa valer para todos os caminhos de escrita.

### Proteção compartilhada nas operações rápidas

O serviço de utilidades receberá uma verificação comum para localizar um produto dentro do escopo, mantendo as atualizações condicionadas ao par produto/igreja. O controlador retornará erro JSON para chamadas AJAX e redirecionamento com mensagem para chamadas de formulário, seguindo o comportamento já usado pelos fluxos legados.

Alternativa considerada: filtrar somente no controlador. Rejeitada porque deixaria chamadas diretas ao serviço vulneráveis e repetiria a regra em vários endpoints.

### Compatibilidade com esquemas legados

A consulta da administração usará a relação existente da igreja e não exigirá `administracao_id` em `produtos`. Nenhuma migração será executada; o esquema de teste criará apenas as colunas já necessárias para exercitar a regra.

## Risks / Trade-offs

- [Risco] Sessões antigas podem não conter as administrações permitidas. → [Mitigação] Manter compatibilidade com a administração ativa e tratar uma sessão explicitamente restrita sem IDs como sem escopo, bloqueando a escrita.
- [Risco] Operações rápidas legadas podem esperar resposta de sucesso mesmo em erro. → [Mitigação] Preservar o formato JSON/redirect atual e cobrir ambos os caminhos com testes.
- [Risco] O binding de produto pode carregar registro fora do escopo antes do serviço. → [Mitigação] Revalidar no serviço antes de salvar; a leitura antecipada não concede capacidade de alteração.

## Migration Plan

Nenhuma migração de dados. Após o deploy, testar a listagem e uma operação de escrita com usuário restrito e administrador. O rollback consiste em reverter o commit, sem alterar banco ou sessões existentes.
