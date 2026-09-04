## Context

A rotina já percorre arquivos temporários de análise e consulta o registro da importação antes de decidir o que remover. O banco possui quatro estados de importação conhecidos: aguardando, processando, concluida e erro. O comportamento atual trata todo estado diferente dos dois ativos como terminal, o que é inseguro diante de dados inconsistentes ou de futuros estados.

## Goals / Non-Goals

**Goals:**

- Tornar a classificação explícita e conservadora.
- Manter a remoção de arquivos sem registro e de importações comprovadamente terminais.
- Garantir que a simulação e a remoção opcional no banco usem a mesma classificação.
- Fornecer feedback operacional para arquivos preservados por estado desconhecido.

**Non-Goals:**

- Alterar o esquema ou os estados permitidos no banco.
- Criar uma rotina agendada ou mudar o local de armazenamento.
- Reprocessar análises, alterar importações ou remover arquivos que não sigam o padrão esperado.

## Decisions

### Classificação por lista explícita de estados terminais

A rotina deverá manter uma lista local de estados removíveis, contendo somente `concluida` e `erro`. A ausência do registro continua sendo uma classificação removível independente do estado. Qualquer registro encontrado fora dessa lista será preservado, inclusive quando o valor estiver vazio.

A alternativa de manter uma lista de estados ativos e considerar o restante terminal é menor, mas falha aberta: cada estado novo ou dado corrompido passa a permitir exclusão. A lista de terminais falha fechada e protege a análise.

### Uma classificação compartilhada para arquivo e banco

O resultado da classificação deve carregar uma indicação inequívoca de que o arquivo é removível. O laço de execução e a opção de remoção de registros devem consultar essa indicação, em vez de repetir regras baseadas apenas no texto do estado. Assim, `--force-delete` não poderá apagar o registro de uma importação preservada.

### Mensagem operacional sem alterar o código de saída

Estados desconhecidos devem gerar uma mensagem de aviso identificando a importação e seu estado, mas a rotina deve continuar processando os demais arquivos e retornar sucesso quando não houver falha de infraestrutura. A preservação é uma decisão segura, não um erro de execução.

## Risks / Trade-offs

- **[Risco]** Arquivos ligados a um estado novo permanecerão no armazenamento até a regra ser atualizada. → **Mitigação:** o aviso identifica o caso para permitir acompanhamento sem perda de dados.
- **[Risco]** Valores de estado com espaços ou capitalização inesperada podem ser preservados. → **Mitigação:** a classificação usa comparação estrita; só estados exatamente reconhecidos são removíveis.
- **[Risco]** Falha ao excluir registros do banco pode ocorrer depois da remoção do arquivo, como já acontece hoje. → **Mitigação:** manter o tratamento de erro existente e a execução explícita via opção de força.

## Migration Plan

Nenhuma migração de dados é necessária. Após a publicação, a próxima execução da rotina adotará a classificação conservadora; arquivos anteriormente preservados poderão ser removidos quando estiverem em estado terminal conhecido.

Para rollback, reverter o commit da aplicação e publicar novamente. A mudança não altera registros existentes nem exige intervenção no banco.
