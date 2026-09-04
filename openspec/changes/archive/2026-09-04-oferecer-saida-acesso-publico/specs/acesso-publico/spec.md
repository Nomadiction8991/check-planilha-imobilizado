## Purpose

Permite encerrar com clareza uma sessão de atendimento público antes de devolver o dispositivo para outra pessoa, sem exigir autenticação administrativa.

## ADDED Requirements

### Requirement: Saída visível do acesso público

A interface SHALL exibir uma ação identificável para encerrar o acesso público quando uma sessão pública estiver ativa, tanto no cabeçalho visível quanto no menu adaptado para telas pequenas.

#### Scenario: Ação disponível durante atendimento público

- GIVEN uma pessoa iniciou o acesso público e está consultando uma igreja
- WHEN a página compartilhada for renderizada
- THEN a interface SHALL exibir uma ação com texto ou rótulo acessível indicando "Sair do acesso público"

#### Scenario: Ação administrativa não aparece no acesso público

- GIVEN uma sessão pública está ativa sem usuário administrativo autenticado
- WHEN o layout for renderizado
- THEN a interface SHALL não exibir a ação de logout administrativo
- AND SHALL exibir somente a saída do contexto público

### Requirement: Encerramento seguro do acesso público

A ação de saída pública SHALL enviar uma requisição POST protegida contra falsificação e SHALL remover todas as chaves de sessão que identificam o atendimento público.

#### Scenario: Encerrar atendimento público

- GIVEN uma sessão pública ativa com igreja selecionada
- WHEN a pessoa aciona "Sair do acesso público"
- THEN o sistema SHALL invalidar os dados públicos da sessão
- AND SHALL redirecionar para a tela de seleção de igreja pública

#### Scenario: Repetir saída sem sessão pública

- GIVEN não existe uma sessão pública ativa
- WHEN a pessoa envia a saída pública
- THEN o sistema SHALL responder com redirecionamento para a tela de seleção pública
- AND SHALL não produzir erro de sessão inexistente
