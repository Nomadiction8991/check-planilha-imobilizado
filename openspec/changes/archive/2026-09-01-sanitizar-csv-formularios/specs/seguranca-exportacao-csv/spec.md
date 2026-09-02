## ADDED Requirements

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
