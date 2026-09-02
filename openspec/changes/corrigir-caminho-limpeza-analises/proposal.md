## Why

A limpeza automática das análises procura os arquivos em um caminho relativo ao código do comando, que não corresponde ao diretório real de armazenamento da aplicação. Assim, a rotina pode concluir que não existem arquivos e deixar resíduos ocupando espaço após as importações.

## What Changes

- Corrigir a resolução do diretório padrão das análises para usar o armazenamento da aplicação.
- Manter a possibilidade de injetar um diretório alternativo em testes e operações controladas.
- Cobrir o caminho padrão com teste de regressão, sem acessar dados reais de produção.

## Capabilities

### New Capabilities

### Modified Capabilities

- `importacao-imobilizado`: a limpeza de arquivos temporários deve consultar o mesmo diretório usado pela persistência das análises.

## Impact

A alteração afeta somente o comando de manutenção de arquivos temporários e seus testes unitários. Não altera o formato das análises, o processamento das importações, registros persistidos ou o banco de produção.
