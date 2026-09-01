## Why

Na listagem de igrejas, a ação de exclusão em lote de produtos utilizava um script inline dedicado que interceptava o submit e disparava uma confirmação assíncrona com `window.confirm`, destoando do padrão declarativo unificado do layout (`data-confirm`) aplicado nos demais cadastros. Adicionalmente, quando o JavaScript é executado ou desabilitado, um fallback padronizado e mensagens claras de confirmação no formulário garantem maior robustez de UX e previnem exclusões acidentais.

## What Changes

- Padroniza o formulário de exclusão de produtos por igreja na view `churches/index.blade.php` com o atributo declarativo `data-confirm`.
- Mantém o enriquecimento progressivo da contagem dinâmica de produtos se disponível, preservando a confirmação base mesmo em caso de falha de rede.
- Adiciona testes automatizados de regressão cobrindo a presença do atributo declarativo `data-confirm` e a ausência de chamadas inline ad-hoc.

## Capabilities

### Modified Capabilities
- `confirmacao-acoes-destrutivas`: Inclusão da confirmação declarativa no formulário de exclusão de produtos por igreja.

## Impact

- `resources/views/churches/index.blade.php`: Formulário passa a incluir `data-confirm` declarativo padrão.
- `tests/Feature/LegacyChurchManagementTest.php`: Cobertura de teste para garantir a presença do `data-confirm` na listagem de igrejas.
