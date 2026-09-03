## Why

A listagem e a verificação de produtos exibem o nome atual considerando edições, mas continuam mostrando o tipo de bem e a dependência originais. Depois de uma edição, essa classificação contraditória dificulta a conferência e pode levar a etiquetas ou decisões no local errado, especialmente no uso mobile.

## What Changes

- Exibir o tipo de bem e a dependência atualmente válidos para cada produto nas telas de produtos e verificação.
- Considerar a classificação editada quando o produto estiver marcado como editado, com fallback seguro para a classificação original quando a edição não tiver relação válida.
- Carregar as relações editadas junto com a paginação, evitando consultas adicionais por linha.
- Cobrir a escolha da classificação atual e a renderização nas duas telas com testes automatizados.

## Capabilities

### New Capabilities

### Modified Capabilities

- `produtos-listagem`: as telas de listagem e verificação passam a apresentar a classificação atual do patrimônio, e não apenas os vínculos originais.

## Impact

Serão afetados o modelo e o serviço de consulta de produtos, um suporte compartilhado de apresentação, as views de listagem/verificação e seus testes. Não haverá alteração de banco, rota, permissão ou dependência externa. O fallback preserva o comportamento dos registros sem edição válida.
