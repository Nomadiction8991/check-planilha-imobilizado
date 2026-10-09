# Design Técnico: Leitor e Parser Universal de Planilhas

## Arquitetura

### 1. `App\Support\UniversalSpreadsheetReader`
- **Leitor OLE2 / BIFF8 (`.xls`):** Decodifica o contêiner binário OLE2, localiza o fluxo `Workbook` e extrai células de texto e números de ponto flutuante diretamente. Converte valores de datas seriais do Excel para `DD/MM/AAAA`.
- **Leitor OpenXML (`.xlsx`):** Utiliza extensões padrão do PHP (`ZipArchive` e `SimpleXML`) para extrair a tabela de strings compartilhadas (`sharedStrings.xml`) e as células da planilha ativa.
- **Leitor Delimitado (`.csv`):** Normaliza encodings e lê fluxos de texto com delimitadores detectados automaticamente.
- **Saída:** Matriz bidimensional normalizada `array<int, array<int, string>>`.

### 2. Validação e Interface
- Atualização de `StoreSpreadsheetImportRequest` com `extensions:csv,txt,xls,xlsx`.
- Atualização da view `resources/views/spreadsheets/import.blade.php` com `accept=".csv,.txt,.xls,.xlsx"`.
