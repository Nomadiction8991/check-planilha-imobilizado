## Why

O acesso público permite consultar uma igreja para atendimento sem autenticação, mas o layout compartilhado não oferece uma saída explícita para encerrar esse contexto. Isso deixa a sessão pública ativa no navegador e torna fácil expor a próxima pessoa ao cadastro selecionado. A saída deve ser visível e segura, especialmente em dispositivos móveis compartilhados.

## What Changes

- Exibir no layout uma ação de saída específica quando houver uma sessão de acesso público.
- Encerrar somente os dados da sessão pública, sem depender da autenticação administrativa.
- Redirecionar a pessoa para a tela de seleção pública após sair, permitindo iniciar outro atendimento sem retornar ao login administrativo.
- Garantir que a ação use POST e a proteção CSRF existente.
- Manter o menu e a saída administrativa inalterados para usuários autenticados.

## Capabilities

### New Capabilities

- `acesso-publico`: saída explícita e segura do contexto de atendimento público.

### Modified Capabilities

- `public-access`: ajustar o destino e o comportamento da saída do acesso público.

## Impact

A mudança afeta as rotas de acesso público, o controlador responsável pela sessão pública e o layout compartilhado em suas versões desktop e mobile. Serão adicionados testes de fluxo e de renderização, sem alterar dados persistidos nem dependências externas.
