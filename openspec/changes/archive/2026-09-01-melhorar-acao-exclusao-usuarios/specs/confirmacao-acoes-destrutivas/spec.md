## MODIFIED Requirements

### Requirement: Formulários destrutivos exigem confirmação explícita
O sistema SHALL solicitar confirmação antes de enviar formulários marcados como destrutivos, utilizando o atributo declarativo `data-confirm` nos formulários de exclusão de usuários, administrações, dependências, tipos de bem e exclusão de produtos por igreja, com texto específico da ação e impedindo o envio quando a pessoa recusar.

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

#### Scenario: Pessoa confirma exclusão de produtos da igreja
- **WHEN** pessoa aciona formulário de exclusão de produtos de uma igreja e confirma a caixa de diálogo
- **THEN** formulário com método POST é enviado ao servidor

#### Scenario: Pessoa cancela exclusão de produtos da igreja
- **WHEN** pessoa aciona formulário de exclusão de produtos de uma igreja e cancela a confirmação
- **THEN** formulário não é enviado

#### Scenario: Pessoa confirma exclusão de usuário
- **WHEN** pessoa aciona formulário destrutivo e confirma mensagem que identifica exclusão do usuário
- **THEN** formulário é enviado normalmente

#### Scenario: Pessoa recusa exclusão de usuário
- **WHEN** pessoa aciona formulário destrutivo e recusa confirmação que identifica exclusão do usuário
- **THEN** formulário não é enviado

#### Scenario: Formulário comum sem confirmação
- **WHEN** pessoa envia formulário sem marcação destrutiva
- **THEN** formulário segue sem confirmação adicional
