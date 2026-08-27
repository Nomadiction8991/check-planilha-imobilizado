## Purpose

Define consistent, explicit confirmation behavior before destructive form submissions, reducing accidental data loss while preserving keyboard and touch usability.

## ADDED Requirements

### Requirement: Formulários destrutivos exigem confirmação explícita
O sistema SHALL solicitar confirmação antes de enviar formulário marcado como destrutivo, usando texto específico da ação e impedindo envio quando a pessoa recusar.

#### Scenario: Pessoa confirma exclusão
- **WHEN** pessoa aciona formulário destrutivo e confirma mensagem que identifica exclusão do usuário
- **THEN** formulário é enviado normalmente

#### Scenario: Pessoa recusa exclusão
- **WHEN** pessoa aciona formulário destrutivo e recusa confirmação
- **THEN** formulário não é enviado

#### Scenario: Formulário comum
- **WHEN** pessoa envia formulário sem marcação destrutiva
- **THEN** formulário segue sem confirmação adicional
