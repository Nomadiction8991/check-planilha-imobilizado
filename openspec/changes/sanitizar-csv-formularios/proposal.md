# Proposal: Sanitizar CSV dos formulários de relatório

## Why

Os CSVs dos formulários 14.1/14.6 e do backup da posição de verificação exportam campos textuais vindos do cadastro (descrição, dependência, observação) sem neutralização. Planilhas podem interpretar valores iniciados por `=`, `+`, `-`, `@`, tab ou `\r` como fórmulas. A auditoria já neutraliza; os relatórios ainda não.

## What Changes

- Estender a proteção de fórmulas (prefixo com apóstrofo) para os exports de relatório: `downloadFormularioCsv` (14.1/14.6), `downloadVerificationBackupCsv` e `downloadPositionCsv`.
- Sanitizar apenas campos textuais controlados por usuários (descrições, dependências, observações, fornecedor). Manter valores sistêmicos (código, datas, números) intactos.
- Centralizar a função de sanitização em `LegacyReportService` para reuso.

## Capabilities

### Modified Capabilities

- `seguranca-exportacao-csv`: A neutralização de fórmulas passa a cobrir também os CSVs de relatórios (formulários e posição de verificação), mantendo o mesmo contrato (apóstrofo no primeiro caractere perigoso).

### New Capabilities

- Nenhuma nova capacidade isolada — é extensão da proteção já existente.

## Impact

Afeta apenas a camada de exportação CSV de relatórios. Não altera filtros, persistência, banco nem layout. Usuários verão apóstrofo apenas em células textuais que começam com caractere perigoso; demais valores permanecem idênticos.
