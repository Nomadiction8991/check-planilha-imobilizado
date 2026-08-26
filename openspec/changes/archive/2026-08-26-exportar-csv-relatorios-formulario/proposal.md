# Proposal: Exportar CSV dos Relatórios de Formulário

## Why

Os relatórios de formulário com dados reais (14.1 e 14.6) hoje só podem ser
visualizados e impressos. A imobiliária/congregação precisa dos dados em CSV
para conferência externa, arquivamento e cruzamento com outras planilhas —
hoje isso exige copiar dados manualmente da prévia impressa.

## What Changes

- Nova rota de download de CSV para o relatório de formulário selecionado,
  protegida pela mesma permissão de visualização (`reports.view`).
- Novo método no serviço de relatórios que monta o CSV a partir das mesmas
  consultas usadas na prévia (14.1: bens marcados; 14.6: bens editados),
  com BOM UTF-8, separador `;` e nome de arquivo com código da igreja e
  data/hora (mesmo padrão do backup já existente da posição).
- Botão "Baixar CSV" na tela de prévia do formulário.
- Formulários sem dados (folha em branco) continuam sem exportação: a rota
  responde com redirecionamento amigável informando que não há itens.

## Capabilities

### New Capabilities
- `relatorios-formulario`: Exportação CSV dos formulários 14.1 e 14.6.

### Modified Capabilities

## Impact

Serviço de relatórios (+ interface/contrato), controlador de relatórios,
rotas e view de prévia. Testes unitários e de feature cobrindo sucesso,
igreja/formulário inválidos e ausência de itens.
