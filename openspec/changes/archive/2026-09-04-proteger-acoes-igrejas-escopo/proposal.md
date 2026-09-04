## Why

A listagem de igrejas já respeita o escopo administrativo, mas as ações auxiliares ainda aceitam um identificador de igreja diretamente na requisição. Um usuário restrito pode consultar contagens, editar ou excluir produtos de uma igreja fora das opções que lhe foram autorizadas, criando uma divergência de autorização entre leitura e escrita.

## What Changes

- Aplicar o escopo administrativo às ações auxiliares da tela de igrejas.
- Impedir contagem e exclusão de produtos para igrejas fora do escopo permitido.
- Impedir edição de uma igreja fora do escopo, inclusive quando o identificador é enviado diretamente.
- Preservar o acesso global dos administradores e as respostas existentes para igrejas válidas.
- Retornar mensagens seguras e específicas quando a igreja não estiver disponível para o usuário.

## Capabilities

### New Capabilities

### Modified Capabilities

- `churches`: ações de consulta, edição e exclusão de produtos passam a respeitar o escopo administrativo autorizado.

## Impact

A mudança afeta o controlador e o serviço de gerenciamento de igrejas, além dos testes de autorização. Não altera rotas, banco de dados ou o escopo de administradores globais. A contagem fora do escopo continuará sendo uma resposta JSON de erro, enquanto edição e exclusão continuarão redirecionando para a listagem com feedback de erro.
