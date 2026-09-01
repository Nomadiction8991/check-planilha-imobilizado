# Proposal: Sanitizar CSV de correção de erros de importação contra injeção de fórmulas

## Why

O CSV de correção de erros de importação (`downloadImportErrorsCsv`) exporta campos textuais vindos de planilhas importadas ou cadastros (`Nome`, `Localidade`, `Dependencia`) sem neutralização de caracteres especiais de fórmulas. Usuários ao abrir esse arquivo no Excel ou LibreOffice podem estar expostos a interpretações de comandos maliciosos caso os dados contenham prefixos perigosos (`=`, `+`, `-`, `@`, `\t`, `\r`). A auditoria e os relatórios já contam com neutralização por apóstrofo; os erros de importação precisam do mesmo tratamento de segurança.

## What Changes

- Aplicar a proteção de fórmulas (prefixando apóstrofo `'` caso o primeiro caractere seja `=`, `+`, `-`, `@`, `\t` ou `\r`) nos campos textuais exportados no CSV de correção de erros (`Nome`, `Localidade`, `Dependencia`).
- Manter valores sistêmicos/identificadores (código) intactos quando não representarem texto arbitrário com prefixo inseguro.
- Adicionar cobertura de testes unitários e de feature garantindo a neutralização correta sem alterar o layout das 16 colunas compatíveis com o modelo de importação.

## Capabilities

### Modified Capabilities

- `seguranca-exportacao-csv`: A neutralização de fórmulas passa a cobrir também o CSV de correção de erros de importação (`downloadImportErrorsCsv`), garantindo consistência em todas as exportações do sistema.

### New Capabilities

- Nenhuma nova capacidade isolada.

## Impact

Afeta a geração de linhas de dados do CSV de correção de erros de importação em `LegacySpreadsheetImportService`. Não altera o fluxo de resolução de erros, nem o banco de dados, nem a compatibilidade de colunas (índices 0, 3, 10 e 15).
