## Why

Quando a exportação da auditoria não encontra eventos, o redirecionamento usa nomes internos dos filtros em vez dos parâmetros da URL. A tela volta sem preservar corretamente busca, módulo e período, obrigando o usuário a refazer a consulta.

## What Changes

- Preservar filtros atuais ao redirecionar após exportação vazia.
- Converter nomes internos de filtros para os nomes públicos usados pela tela.
- Cobrir busca, módulo e datas com teste de regressão.

## Capabilities

### New Capabilities

### Modified Capabilities

- `auditoria`: preservar filtros públicos no retorno da exportação sem resultados.

## Impact

A alteração afeta o redirecionamento do controlador de auditoria e seus testes de feature. Não muda formato CSV, permissões, persistência ou contrato do serviço de auditoria.
