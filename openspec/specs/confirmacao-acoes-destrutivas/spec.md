# confirmacao-acoes-destrutivas Specification

## Purpose
Define explicit, declarative confirmation behavior for destructive deletion actions in administrations, departments, and asset types management tables.
## Requirements
### Requirement: Formulários destrutivos exigem confirmação explícita
O sistema SHALL solicitar confirmação antes de enviar formulários marcados como destrutivos, utilizando o atributo declarativo `data-confirm` nos formulários de exclusão de usuários, administrações, dependências e tipos de bem, impedindo o envio quando a pessoa recusar.

#### Scenario: Pessoa confirma exclusão de administração
- **WHEN** pessoa aciona formulário de exclusão de administração e confirma a caixa de diálogo
- **THEN** formulário com método DELETE é enviado ao servidor

#### Scenario: Pessoa cancela exclusão de administração
- **WHEN** pessoa aciona formulário de exclusão de administração e cancela a confirmação
- **THEN** formulário não é enviado

#### Scenario: Pessoa confirma exclusão de dependência
- **WHEN** pessoa aciona formulário de exclusão de dependência e confirma a caixa de diálogo
- **THEN** formulário com método DELETE é enviado ao servidor

#### Scenario: Pessoa confirma exclusão de tipo de bem
- **WHEN** pessoa aciona formulário de exclusão de tipo de bem e confirma a caixa de diálogo
- **THEN** formulário com método DELETE é enviado ao servidor

