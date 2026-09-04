## Why

A tela de relatórios filtra igrejas por administração e estado, mas o serviço ainda carrega opções de todas as administrações e aceita diretamente um identificador de igreja em prévias, posição de estoque e exportações. Um usuário restrito pode, portanto, consultar dados fora das administrações permitidas manipulando a URL, mesmo quando a listagem de produtos já aplica escopo.

## What Changes

- Aplicar às opções de administrações e igrejas dos relatórios o mesmo escopo administrativo já usado na consulta de produtos.
- Validar no backend a igreja solicitada antes de gerar prévias, posição de estoque, histórico e arquivos CSV.
- Rejeitar igrejas fora do escopo com erro controlado, sem carregar dados do relatório.
- Preservar acesso global para administradores e o comportamento existente para igrejas permitidas.
- Cobrir opções filtradas e acesso direto às operações de relatório com testes de regressão.

## Capabilities

### New Capabilities

### Modified Capabilities

- `relatorios-listagem`: restringe as opções de administração e igreja ao escopo autorizado do usuário.
- `relatorios-formulario`: impede geração e exportação de relatórios para igrejas fora do escopo autorizado.

## Impact

A mudança afeta o serviço de relatórios e seus testes. Não altera tabelas, rotas, permissões, formato dos CSVs ou contratos de administradores; apenas acrescenta a validação de escopo antes da leitura dos dados. As respostas de erro existentes dos controladores continuam sendo usadas para orientar o usuário.