# seguranca-exportacao-csv Specification

## Purpose
Protege exportações com dados textuais contra interpretação acidental como fórmulas por aplicativos de planilha, mantendo valores legítimos e formato CSV utilizável.
## Requirements
### Requirement: Neutralização de fórmulas em exportações

O sistema SHALL prefixar com apóstrofo campos textuais exportados cujo primeiro caractere seja `=`, `+`, `-`, `@`, tabulação ou retorno de carro.

#### Scenario: Texto perigoso é neutralizado

- **WHEN** usuário exporta auditoria contendo descrição iniciada por `=1+1`
- **THEN** CSV contém descrição iniciada por `'=1+1`, sem expor payload cru como célula exportada

#### Scenario: Texto legítimo permanece igual

- **WHEN** usuário exporta auditoria contendo nome, ação ou descrição sem caractere perigoso inicial
- **THEN** CSV preserva valor original sem apóstrofo adicional

#### Scenario: Valores sistêmicos permanecem intactos

- **WHEN** sistema exporta data, status HTTP ou identificador numérico
- **THEN** CSV preserva valor sistêmico sem prefixo de proteção

### Requirement: Neutralização de Fórmulas em Exportação de Erros de Importação
O sistema SHALL sanitizar campos textuais exportados no arquivo CSV de correção de erros de importação (`downloadImportErrorsCsv`), adicionando o prefixo `'` quando o primeiro caractere for `=`, `+`, `-`, `@`, `\t` ou `\r`.

#### Scenario: Campo textual iniciado com operador de fórmula
- GIVEN que existem erros de importação cadastrados com nome `=SOMA(1;2)` ou localidade `@COMUM` ou dependência `+SALA 01`
- WHEN o usuário solicitar o download do CSV de correção de erros de importação
- THEN as células textuais correspondentes no CSV devem iniciar com apóstrofo (`'=SOMA(1;2)`, `'@COMUM`, `'+SALA 01`)
- AND a estrutura posicional de 16 colunas deve ser preservada

### Requirement: Sanitização de CSV nos relatórios protege texto controlado por usuários

Os CSVs de relatórios (formulários 14.1/14.6, backup de verificação e posição) SHALL prefixar com apóstrofo campos textuais cujo primeiro caractere seja `=`, `+`, `-`, `@`, tabulação ou retorno de carro, preservando valores sistêmicos sem alteração.

#### Scenario: Descrição iniciada por fórmula no formulário 14.1

- **WHEN** o usuário exporta o formulário 14.1 com bem cuja descrição começa com `=1+1`
- **THEN** o CSV contém a célula iniciada por `'=1+1`, não o payload cru

#### Scenario: Dependência perigosa no formulário 14.6

- **WHEN** o usuário exporta o formulário 14.6 com dependência iniciada por `@evil`
- **THEN** o CSV contém `\'@evil` na célula de dependência

#### Scenario: Texto legítimo permanece igual

- **WHEN** o usuário exporta relatório com descrição normal (ex.: `CADEIRA METÁLICA`)
- **THEN** o CSV preserva o valor original sem apóstrofo adicional

#### Scenario: Valores sistêmicos não são prefixados

- **WHEN** o sistema exporta código, data ou flags numéricas
- **THEN** o CSV preserva esses valores sem prefixo de proteção

