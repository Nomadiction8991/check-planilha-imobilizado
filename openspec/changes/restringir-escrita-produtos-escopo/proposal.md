## Why

A listagem de produtos já limita o que usuários restritos conseguem consultar, mas os fluxos de criação, edição e operações rápidas ainda aceitam IDs de outra administração quando alguém manipula a requisição. Isso permite alterar o inventário fora do escopo autorizado e deixa a proteção dependente apenas da interface.

## What Changes

- Validar o vínculo da igreja escolhida com as administrações permitidas antes de criar produtos.
- Validar o escopo do produto e da igreja antes de editar ou executar operações rápidas de verificação, etiqueta, observação, assinatura e limpeza.
- Rejeitar requisições fora do escopo com mensagem amigável, sem alterar dados.
- Cobrir os caminhos de escrita com testes de regressão.

## Capabilities

### New Capabilities

- `escrita-produtos-escopo`: protege operações de criação, edição e atualização do inventário conforme o escopo de administração do usuário.

### Modified Capabilities

- `produtos-listagem`: complementa a proteção de escopo da consulta com a mesma regra nas operações que alteram produtos.

## Impact

Serão afetados os serviços de gerenciamento e utilidades de produtos, o controlador das operações rápidas, as rotas de criação/edição/verificação e seus testes. Não há alteração de banco, dependência externa ou contrato para administradores, que continuarão com acesso global.
