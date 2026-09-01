# Delta Spec: Sanitização de Fórmulas no CSV de Erros de Importação

## ADDED Requirements

### Requirement: Neutralização de Fórmulas em Exportação de Erros de Importação
O sistema SHALL sanitizar campos textuais exportados no arquivo CSV de correção de erros de importação (`downloadImportErrorsCsv`), adicionando o prefixo `'` quando o primeiro caractere for `=`, `+`, `-`, `@`, `\t` ou `\r`.

#### Scenario: Campo textual iniciado com operador de fórmula
- GIVEN que existem erros de importação cadastrados com nome `=SOMA(1;2)` ou localidade `@COMUM` ou dependência `+SALA 01`
- WHEN o usuário solicitar o download do CSV de correção de erros de importação
- THEN as células textuais correspondentes no CSV devem iniciar com apóstrofo (`'=SOMA(1;2)`, `'@COMUM`, `'+SALA 01`)
- AND a estrutura posicional de 16 colunas deve ser preservada
