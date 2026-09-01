## Why

A exclusão de usuário usa confirmação inline, misturando comportamento JavaScript à marcação e dificultando manutenção e testes. A ação precisa declarar sua intenção de forma clara e permitir tratamento consistente no layout.

## What Changes

- Substituir confirmação inline por atributo declarativo na exclusão de usuário.
- Centralizar confirmação de formulários destrutivos no layout compartilhado.
- Preservar bloqueio da submissão quando a pessoa recusar a exclusão.

## Capabilities

### New Capabilities

- `confirmacao-acoes-destrutivas`: Confirmação consistente e acessível para formulários destrutivos.

### Modified Capabilities

## Impact

A alteração afeta a tela de usuários e o JavaScript do layout de migração. Não altera rotas, banco ou API.
