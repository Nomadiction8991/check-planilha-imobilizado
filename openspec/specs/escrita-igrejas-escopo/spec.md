# escrita-igrejas-escopo Specification

## Purpose
Garante que apenas usuários com escopo autorizado possam alterar dados de igrejas, impedindo troca de administração para fora do escopo.
## Requirements
### Requirement: [ADDED] Edição de igreja respeita escopo administrativo

O sistema SHALL verificar o escopo antes de atualizar uma igreja: usuários restritos só podem editar igrejas cuja administração atual pertence ao seu escopo e só podem atribuir a igreja a uma administração dentro do mesmo escopo; administradores globais permanecem sem restrição.

#### Scenario: Edição bloqueada quando igreja atual está fora do escopo

- WHEN um usuário restrito tenta atualizar uma igreja cuja administração atual não está em seu escopo
- THEN o sistema SHALL rejeitar a operação com mensagem amigável e não alterar o registro

#### Scenario: Edição bloqueada quando nova administração está fora do escopo

- WHEN um usuário restrito tenta mover uma igreja dentro do seu escopo para uma administração fora do escopo
- THEN o sistema SHALL rejeitar a operação com mensagem amigável e não alterar o registro

#### Scenario: Edição permitida dentro do escopo

- WHEN um usuário restrito atualiza uma igreja dentro do escopo para outra administração também dentro do escopo
- THEN o sistema SHALL permitir a atualização normalmente

#### Scenario: Administrador global permanece irrestrito

- WHEN um administrador global edita qualquer igreja, mesmo fora do escopo restrito
- THEN o sistema SHALL permitir a operação sem validação de escopo

