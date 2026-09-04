## Why

A tela de auditoria ainda exige um toque extra em “Filtrar” depois de cada alteração, enquanto as demais listagens principais já atualizam os resultados automaticamente. No celular, esse passo adicional torna a consulta mais lenta e facilita esquecer um filtro alterado antes de exportar ou revisar os eventos.

## What Changes

- Aplicar automaticamente os filtros de administração, módulo e período quando seus valores mudarem.
- Aplicar a busca geral com pequeno atraso após a digitação, evitando uma navegação a cada tecla.
- Exibir estado acessível de atualização e impedir submissões duplicadas enquanto a consulta é enviada.
- Manter o botão “Filtrar” como alternativa manual e preservar o link “Limpar”.

## Capabilities

### New Capabilities

Nenhuma.

### Modified Capabilities

- `auditoria`: a tela de auditoria passa a aplicar automaticamente os filtros server-side, mantendo a consulta manual como alternativa.

## Impact

A mudança afeta somente o comportamento da interface Blade da tela de auditoria e seus testes de página. Não altera rotas, contratos, escopo de autorização, consulta de eventos ou formato da exportação CSV.
