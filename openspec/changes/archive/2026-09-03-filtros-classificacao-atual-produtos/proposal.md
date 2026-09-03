## Why

A busca geral da listagem e da verificação considera apenas a classificação original do produto. Depois de uma conferência, porém, o tipo de bem e a dependência editados passam a representar o estado atual; procurar por esses valores pode não encontrar um item que o usuário consegue ver na tela. A consulta precisa acompanhar a classificação vigente sem expor dados fora do escopo autorizado.

## What Changes

- Ampliar a busca geral de produtos para localizar o código ou a descrição atual do tipo de bem e da dependência, considerando os valores editados quando válidos e os originais como fallback.
- Fazer os filtros de dependência e tipo de bem respeitarem a classificação vigente do produto, preservando o comportamento dos produtos sem edição.
- Manter a consulta compatível com a listagem e a verificação, com relações atuais carregadas sem consultas adicionais por linha.
- Preservar o escopo de administração e igreja aplicado às consultas existentes.

## Capabilities

### New Capabilities

- Nenhuma.

### Modified Capabilities

- `produtos-listagem`: a busca e os filtros de classificação passam a usar a dependência e o tipo de bem atuais, incluindo valores editados válidos.

## Impact

A mudança afeta o serviço de consulta e os testes de listagem de produtos. Não altera rotas, contratos HTTP ou o formato dos parâmetros existentes; os parâmetros atuais continuam sendo usados. A implementação deve funcionar no SQLite dos testes e no PostgreSQL de produção, sem tocar dados persistidos nem alterar a classificação gravada.
