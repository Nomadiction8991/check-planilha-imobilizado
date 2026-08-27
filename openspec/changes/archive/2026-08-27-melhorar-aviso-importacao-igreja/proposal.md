## Why

A tela de importação e a prévia exibem um aviso amarelo sobre "preferir planilha filtrada por igreja" focado em performance, mas não deixam claro que o escopo da importação é a **igreja inteira** (todas as dependências), não apenas a dependência selecionada. Usuários podem achar que ao escolher uma dependência na prévia estão limitando a importação, quando na verdade a importação processa todos os setores daquela igreja. Isso gera confusão e reimportações desnecessárias.

## What Changes

- Alterar o banner de aviso na tela de importação (`resources/views/spreadsheets/import.blade.php`) para:
  - Cor vermelha (destacar risco de escopo)
  - Texto explícito: "A importação processa a **igreja inteira** (todas as dependências), não apenas um setor"
  - Ícone de alerta mais proeminente
- Adicionar aviso equivalente na prévia da importação (`resources/views/spreadsheets/preview.blade.php`) antes da tabela de igrejas detectadas
- Manter o aviso de performance como nota secundária

## Capabilities

### Modified Capabilities
- `importacao-imobilizado`: O comportamento de exibição de avisos na importação e prévia muda (requisito de UX)

## Impact

- Views: `resources/views/spreadsheets/import.blade.php`, `resources/views/spreadsheets/preview.blade.php`
- Testes de feature existentes podem precisar ajustar asserções de texto
- Nenhuma mudança em rotas, controllers, services ou banco de dados