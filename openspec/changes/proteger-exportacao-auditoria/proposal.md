## Why

A exportação da auditoria grava campos controlados por usuários diretamente no CSV. Planilhas podem interpretar valores iniciados por `=`, `+`, `-`, `@`, tabulação ou retorno de carro como fórmulas, criando risco de execução ou exfiltração ao abrir o arquivo. A proteção precisa existir na saída, mesmo quando valor veio de importação legítima.

## What Changes

- Neutralizar caracteres de fórmula no início de campos textuais controlados por usuários na exportação da auditoria.
- Preservar datas, códigos HTTP e demais valores sistêmicos sem alteração.
- Cobrir proteção no serviço e na exportação HTTP com testes de regressão.

## Capabilities

### New Capabilities

- `seguranca-exportacao-csv`: Protege dados textuais exportados contra interpretação como fórmula por planilhas.

### Modified Capabilities

- `auditoria`: A exportação CSV passa a neutralizar valores textuais perigosos antes do download.

## Impact

Afeta o serviço de exportação de auditoria e seus testes. Não altera formato de colunas, filtros, escopo, persistência nem banco de dados. Usuários verão um apóstrofo prefixando texto que começa com caractere interpretável como fórmula.
