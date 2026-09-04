## Why

A rotina de limpeza identifica qualquer situação diferente de uma importação ativa como órfã. Se surgir um novo estado de processamento ou um valor inesperado no banco, a rotina pode apagar uma análise ainda necessária. A limpeza deve ser conservadora: somente estados comprovadamente terminais podem ser removidos automaticamente.

## What Changes

- Restringir a remoção automática às importações com estado terminal conhecido (`concluida` ou `erro`) e aos arquivos cujo registro não existe.
- Preservar arquivos ligados a estados desconhecidos, vazios ou futuros e informar que foram mantidos.
- Manter o modo de simulação e a remoção opcional de registros do banco sujeitos à mesma classificação segura.
- Cobrir estados não reconhecidos com teste de regressão.

## Capabilities

### New Capabilities

- `limpeza-analises`: classificação conservadora e remoção segura dos arquivos temporários de análise.

### Modified Capabilities

- Nenhuma.

## Impact

A rotina de manutenção de análises e seus testes serão ajustados. Não há alteração de rotas, banco de dados, formato dos arquivos ou dependências externas. Estados já suportados continuam com o comportamento atual; apenas estados não reconhecidos deixam de ser tratados como órfãos.
