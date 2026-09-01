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

