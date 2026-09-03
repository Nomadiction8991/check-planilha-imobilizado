# escrita-dependencias-escopo Specification

## Purpose
Garante que criação, edição e exclusão de dependências ocorram apenas dentro do escopo autorizado de igreja/administração.
## Requirements
### Requirement: [ADDED] Escrita de dependências respeita escopo da igreja vinculada

O sistema SHALL exigir que a igreja vinculada à operação (criação com nova igreja, edição com nova igreja ou exclusão da dependência atual) esteja dentro do escopo do usuário restrito; administradores globais permanecem irrestritos.

#### Scenario: Criação bloqueada quando igreja alvo está fora do escopo

- WHEN um usuário restrito tenta criar dependência vinculada a igreja fora do escopo
- THEN o sistema SHALL rejeitar a criação com mensagem amigável e não criar registro

#### Scenario: Edição bloqueada quando nova igreja está fora do escopo

- WHEN um usuário restrito tenta mover dependência para igreja fora do escopo
- THEN o sistema SHALL rejeitar a atualização com mensagem amigável e não alterar o registro

#### Scenario: Edição bloqueada quando dependência atual está fora do escopo

- WHEN um usuário restrito tenta editar dependência cuja igreja atual está fora do escopo
- THEN o sistema SHALL rejeitar a operação com mensagem amigável e não alterar o registro

#### Scenario: Exclusão bloqueada quando dependência atual está fora do escopo

- WHEN um usuário restrito tenta excluir dependência cuja igreja atual está fora do escopo
- THEN o sistema SHALL rejeitar a exclusão com mensagem amigável e não remover o registro

#### Scenario: Operações permitidas dentro do escopo

- WHEN o usuário restrito cria/edita/exclui dependência cuja igreja (nova e atual) está dentro do escopo
- THEN o sistema SHALL permitir a operação normalmente

