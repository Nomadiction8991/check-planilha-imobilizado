## Context

A tela de importação (`resources/views/spreadsheets/import.blade.php`) já possui um banner de aviso (classe `.spreadsheet-warning-banner`) com cor amarela/laranja (`#ffb703`) e texto sobre preferir planilhas filtradas por igreja para performance. A prévia (`resources/views/spreadsheets/preview.blade.php`) também tem um banner semelhante com cor teal (`#2ec4b6`). Ambos precisam ser alterados para vermelho com texto explícito sobre escopo de igreja inteira.

## Goals / Non-Goals

**Goals:**
- Alterar o banner da tela de importação para vermelho com texto claro sobre escopo
- Adicionar banner equivalente na prévia antes da tabela de igrejas detectadas
- Manter consistência visual entre os dois banners
- Preservar o aviso de performance como nota secundária

**Non-Goals:**
- Mudar lógica de importação, controllers, services ou banco de dados
- Alterar rotas ou permissões
- Modificar a estrutura de dados da análise de importação

## Decisions

1. **Reutilizar a classe CSS existente `.spreadsheet-warning-banner`** alterando apenas as cores (background, border) e o conteúdo textual. Isso mantém a animação de entrada e o layout responsivo já existentes.

2. **Na tela de importação**: Substituir o banner amarelo por vermelho. Texto principal: "A importação processa a **igreja inteira** (todas as dependências), não apenas um setor". Nota secundária: "Para análise mais leve, prefira planilha filtrada por igreja."

3. **Na prévia**: Adicionar novo banner vermelho imediatamente antes da seção "Igrejas detectadas" (após o painel de erros, se houver). Texto: "A importação processa a **igreja inteira** — ao confirmar, todos os setores da(s) igreja(s) selecionada(s) serão importados".

4. **Cores**: Usar `linear-gradient(135deg, #7f1d1d, #450a0a)` para background (vermelho escuro), borda esquerda `#ef4444` (vermelho 500), texto `#fef2f2` (vermelho 50). Eyebrow: background `rgba(254, 202, 202, 0.16)`, cor `#fca5a5`.

## Risks / Trade-offs

- **Risco**: Usuários acostumados com o banner amarelo podem não notar a mudança de cor inicialmente → Mitigação: O vermelho é mais chamativo e o texto é mais direto.
- **Trade-off**: Dois banners vermelhos (importação + prévia) podem parecer repetitivos → Mitigação: A repetição é intencional para reforçar o escopo em momentos decisórios diferentes.