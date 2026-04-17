# Erros Encontrados - Check Planilha Imobilizado

## Resumo
- Críticos: 0
- Altos: 2
- Médios: 0
- Baixos: 0

## Erros por Categoria
### Segurança (0)
### Lógica (2)
### Performance (0)
### Code Smell (0)

## Detalhes dos Erros

### LEG-001 - Reconhecer admin pela sessão nas listagens

**Arquivo**: `resources/views/administrations/index.blade.php` (linha 7)
**Severidade**: 🟠 ALTO
**Categoria**: Lógica
**Status**: Corrigido nesta rodada.
**Descrição**: As listagens administrativas dependiam só de `legacySessionUser['is_admin']`. Quando o composer do layout retornava sessão parcial ou `legacySessionUser` vinha nulo, o usuário administrador perdia botões de criar e editar em várias telas.

**Risco/Impacto**: A interface ficava inconsistente e o administrador não conseguia acessar ações que deveriam estar liberadas.

**Recomendação**: Usar um fallback explícito com `session('is_admin')` nas telas de listagem ou normalizar a sessão no composer. Correção aplicada nas views afetadas.

**Padrão Recorrente**: Sim
**Prioridade de Correção**: 🚨 Imediata

### IMP-001 - Gravar igreja da importação como nulo

**Arquivo**: `app/Http/Requests/StoreSpreadsheetImportRequest.php` (linha 49)
**Severidade**: 🟠 ALTO
**Categoria**: Lógica
**Status**: Corrigido nesta rodada.
**Descrição**: A importação multi-igreja estava enviando `churchId = 0` para `importacoes.comum_id`. Como essa coluna tem chave estrangeira para `comums.id`, o insert falha ou grava um valor inválido em produção quando não há igreja base.

**Risco/Impacto**: A análise da planilha não avança e o fluxo de importação quebra assim que tenta registrar a importação.

**Recomendação**: Persistir `comum_id` como `NULL` quando não existir igreja base e continuar usando `0` apenas como fallback interno no parser.

**Padrão Recorrente**: Sim
**Prioridade de Correção**: 🚨 Imediata
